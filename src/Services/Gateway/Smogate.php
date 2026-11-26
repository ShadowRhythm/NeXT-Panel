<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Paylist;
use App\Services\Auth;
use App\Services\I18n;
use App\Services\View;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use voku\helper\AntiXSS;
use function array_keys;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function hash_equals;
use function http_build_query;
use function is_numeric;
use function is_string;
use function json_decode;
use function ksort;
use function md5;
use function round;
use function strpos;
use function strtolower;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_URL;

final class Smogate extends Base
{
    public function __construct()
    {
        $this->antiXss = new AntiXSS();
    }

    public static function _name(): string
    {
        return 'smogate';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('smogate');
    }

    public static function _readableName(): string
    {
        return I18n::trans('user.gateway_smogate_alipay_name');
    }

    public function post($data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . Config::obtain('smogate_app_id') . '.vless.org/v1/gateway/pay');
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['User-Agent: Smogate ' . Config::obtain('smogate_app_id')]);
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    public function prepareSign($data)
    {
        ksort($data);
        return http_build_query($data);
    }

    public function sign($data)
    {
        return strtolower(md5($data . Config::obtain('smogate_app_secret')));
    }

    public function verify($data, $signature)
    {
        unset($data['sign']);
        $mySign = $this->sign($this->prepareSign($data));
        return hash_equals($mySign, (string) $signature);
    }

    public function purchase(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = Auth::getUser();
        $locale = I18n::getCurrentLocale($user);
        if ($user === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_login_first', $locale),
            ]);
        }

        $invoice_id = $this->antiXss->xss_clean($request->getParam('invoice_id'));
        $invoice = (new Invoice())->find($invoice_id);

        if ($invoice === null || $invoice->user_id !== $user->id) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.invoice_not_found', $locale),
            ]);
        }

        if ($invoice->status !== 'unpaid' && $invoice->status !== 'partially_paid') {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_invoice_not_payable', $locale),
            ]);
        }

        $price = $invoice->price;
        if ($price <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_order_amount_error', $locale),
            ]);
        }

        $pl = (new Paylist())->where('invoice_id', $invoice_id)->first();
        if ($pl === null) {
            $pl = new Paylist();
            $pl->userid = $user->id;
            $pl->total = $price;
            $pl->invoice_id = $invoice_id;
            $pl->tradeno = self::generateGuid();
        }
        $pl->gateway = self::_readableName();
        $pl->save();

        $data = [
            'method' => 'alipay',
            'app_id' => Config::obtain('smogate_app_id'),
            'out_trade_no' => $pl->tradeno,
            'total_amount' => (int) round($pl->total * 100),
            'notify_url' => self::getCallbackUrl(),
        ];
        $params = $this->prepareSign($data);
        $data['sign'] = $this->sign($params);
        $result = json_decode($this->post($data), true);

        if (isset($result['errors'])) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $result['errors'][array_keys($result['errors'])[0]],
            ]);
        }
        if (isset($result['message'])) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $result['message'],
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'type' => $this->isMobile() ? 'url' : 'qrcode',
            'qrcode' => $result['data'],
            'amount' => $pl->total,
            'pid' => $pl->tradeno,
        ]);
    }

    public function notify($request, $response, $args): ResponseInterface
    {
        $params = (array) ($request->getParsedBody() ?: $request->getQueryParams());
        if (empty($params)) {
            $params = ! empty($_POST) ? $_POST : $_GET;
        }

        $signature = $params['sign'] ?? '';
        if ($signature === '' || ! $this->verify($params, $signature)) {
            return $response->write('FAIL');
        }

        $out_trade_no = $params['out_trade_no'] ?? null;
        if (! is_string($out_trade_no) || $out_trade_no === '') {
            return $response->write('FAIL');
        }

        $actualAmount = null;
        if (isset($params['total_amount']) && is_numeric($params['total_amount'])) {
            $actualAmount = round(((float) $params['total_amount']) / 100.0, 2);
        }

        $this->postPayment($out_trade_no, $actualAmount);
        return $response->write('SUCCESS');
    }

    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/smogate.tpl');
    }

    private function isMobile()
    {
        return strpos(strtolower($_SERVER['HTTP_USER_AGENT'] ?? ''), 'mobile') !== false;
    }
}
