<?php

declare(strict_types=1);

/**
 * Copyright (c) 2019.
 * Author:Alone88
 * Github:https://github.com/anhao
 */

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Paylist;
use App\Services\Auth;
use App\Services\Gateway\Epay\EpayNotify;
use App\Services\Gateway\Epay\EpaySubmit;
use App\Services\Gateway\Epay\EpayTool;
use App\Services\I18n;
use App\Services\View;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use voku\helper\AntiXSS;
use function json_decode;
use function trim;

final class Epay extends Base
{
    protected array $epay = [];

    public function __construct()
    {
        $this->antiXss = new AntiXSS();
        $this->epay['apiurl'] = Config::obtain('epay_url');//易支付API地址
        $this->epay['partner'] = Config::obtain('epay_pid');//易支付商户pid
        $this->epay['key'] = Config::obtain('epay_key');//易支付商户Key
        $this->epay['sign_type'] = strtoupper(Config::obtain('epay_sign_type')); //签名方式
        $this->epay['input_charset'] = strtolower('utf-8');//字符编码
        $this->epay['transport'] = 'https';//协议 http 或者https
    }

    public static function _name(): string
    {
        return 'epay';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('epay');
    }

    public static function _readableName(): string
    {
        return 'EPay';
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
        // EPay 特定参数
        $type = $this->antiXss->xss_clean($request->getParam('type'));
        $redir = self::sanitizeReturnUrl($this->antiXss->xss_clean($request->getParam('redir')));
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
                'msg' => I18n::trans('user.gateway_invalid_amount', $locale),
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

        $type_text = match ($type) {
            'qqpay' => 'QQ',
            'wxpay' => 'WeChat',
            'epusdt' => 'USDT',
            default => 'Alipay',
        };

        $pl->gateway = self::_readableName() . ' ' . $type_text;

        $pl->save();
        //请求参数
        $data = [
            'pid' => trim($this->epay['partner']),
            'type' => $type,
            'out_trade_no' => $pl->tradeno,
            'notify_url' => self::getCallbackUrl(),
            'return_url' => $redir,
            'name' => $pl->tradeno,
            'money' => $price,
            'sitename' => $_ENV['appName'],
            'clientip' => $_SERVER['REMOTE_ADDR'],
        ];

        $epaySubmit = new EpaySubmit($this->epay);
        $data['sign'] = $epaySubmit->buildRequestMysign(EpayTool::argSort($data));
        $data['sign_type'] = $this->epay['sign_type'];
        $client = new Client();

        try {
            $res = json_decode(
                $client->request(
                    'POST',
                    $this->epay['apiurl'] . 'mapi.php',
                    ['form_params' => $data]
                )->getBody()->__toString(),
                true
            );

            if (! is_array($res) || ($res['code'] ?? null) !== 1 || ! self::isValidExternalRedirectUrl($res['payurl'] ?? null)) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => I18n::trans('user.gateway_epay_error', $locale),
                    //TODO: use syslog to log this error
                ]);
            }

            return $response->withHeader('HX-Redirect', $res['payurl']);
        } catch (GuzzleException) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_epay_error', $locale),
            ]);
        }
    }

    public function notify($request, $response, $args): ResponseInterface
    {
        $params = (array) ($request->getParsedBody() ?: $request->getQueryParams());
        if (empty($params)) {
            $params = ! empty($_POST) ? $_POST : $_GET;
        }

        // Verify partner pid if present
        if (isset($params['pid']) && (string) $params['pid'] !== (string) $this->epay['partner']) {
            return $response->write('failed');
        }

        $epayNotify = new EpayNotify($this->epay);
        $verify_result = $epayNotify->verifyNotify($params);

        if ($verify_result) {
            $trade_status = $params['trade_status'] ?? '';
            $out_trade_no = $params['out_trade_no'] ?? '';
            $money = isset($params['money']) && is_numeric($params['money']) ? (float) $params['money'] : null;

            if ($trade_status === 'TRADE_SUCCESS' && $out_trade_no !== '') {
                $this->postPayment($out_trade_no, $money);
                return $response->write('success');
            }
        }

        return $response->write('failed');
    }

    /**
     * @throws Exception
     */
    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/epay.tpl');
    }
}
