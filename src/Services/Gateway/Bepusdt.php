<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Paylist;
use App\Services\Auth;
use App\Services\I18n;
use App\Services\View;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use voku\helper\AntiXSS;
use function is_array;
use function json_decode;
use function md5;
use function ksort;
use function trim;

final class Bepusdt extends Base
{
    protected array $config = [];

    public function __construct()
    {
        $this->antiXss = new AntiXSS();

        $this->config['bepusdt_api_url']   = Config::obtain('bepusdt_api_url');
        $this->config['bepusdt_api_token'] = Config::obtain('bepusdt_api_token');
        $this->config['bepusdt_trade_type']  = Config::obtain('bepusdt_trade_type') ?: '';
        $this->config['bepusdt_expire_time'] = Config::obtain('bepusdt_expire_time') ?: '10';
        $this->config['bepusdt_fiat']        = Config::obtain('bepusdt_fiat') ?: 'CNY';
    }

    public static function _name(): string
    {
        return 'bepusdt';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('bepusdt');
    }

    public static function _readableName(): string
    {
        return I18n::trans('user.gateway_bepusdt_name');
    }

    /**
     * @throws Exception
     */
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

        $invoiceId = $this->antiXss->xss_clean($request->getParam('invoice_id'));
        $invoice   = (new Invoice())->find($invoiceId);

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

        $redir = self::sanitizeReturnUrl($this->antiXss->xss_clean($request->getParam('redir')));

        $pl = (new Paylist())->where('invoice_id', $invoiceId)->first();
        if ($pl === null) {
            $pl            = new Paylist();
            $pl->userid    = $user->id;
            $pl->total     = $price;
            $pl->invoice_id = $invoiceId;
            $pl->tradeno   = self::generateGuid();
        }

        $pl->gateway = self::_readableName();
        $pl->save();

        $apiUrl   = rtrim(trim($this->config['bepusdt_api_url']), '/');
        $apiToken = trim($this->config['bepusdt_api_token']);

        if (! $apiUrl || ! $apiToken) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_bepusdt_not_configured', $locale),
            ]);
        }

        $fiat      = $this->config['bepusdt_fiat'] ?: 'CNY';
        $tradeType = trim((string) ($this->config['bepusdt_trade_type'] ?? ''));

        $params = [
            'order_id'     => $pl->tradeno,
            'amount'       => (float) $price,
            'fiat'         => $fiat,
            'notify_url'   => self::getCallbackUrl(),
            'redirect_url' => $redir ?: self::getUserReturnUrl(),
        ];

        if ($tradeType !== '') {
            $params['trade_type'] = $tradeType;
        }

        $params['signature'] = $this->buildSignature($params, $apiToken);

        // 如果未锁定具体交易类型，优先使用 BEpusdt 的收银台接口 create-order（允许用户在付款页自选/切换网络与币种）
        // 若指定了具体 trade_type，则使用 create-transaction 接口锁定单链
        $endpoint = ($tradeType === '') ? '/api/v1/order/create-order' : '/api/v1/order/create-transaction';

        $ch = curl_init($apiUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        $rawResponse = curl_exec($ch);
        $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError   = curl_error($ch);
        curl_close($ch);

        // 如果 create-order 返回 404（部分旧版服务端未支持收银台路由），自动回退到 create-transaction
        if ($endpoint === '/api/v1/order/create-order' && $httpCode === 404) {
            $ch = curl_init($apiUrl . '/api/v1/order/create-transaction');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            ]);
            $rawResponse = curl_exec($ch);
            $curlError   = curl_error($ch);
            curl_close($ch);
        }

        if ($rawResponse === false) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_bepusdt_request_failed', $locale, ['error' => $curlError]),
            ]);
        }

        $result = json_decode($rawResponse, true);

        if (! is_array($result) || ($result['status_code'] ?? null) !== 200) {
            $errMsg = $result['message'] ?? $rawResponse;
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_order_create_failed', $locale, ['error' => $errMsg]),
            ]);
        }

        $payUrl = $result['data']['payment_url'] ?? null;

        if (! self::isValidExternalRedirectUrl($payUrl)) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_no_payment_url', $locale),
            ]);
        }

        return $response->withHeader('HX-Redirect', $payUrl)->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.gateway_order_created_redirect', $locale),
        ]);
    }

    public function notify($request, $response, $args): ResponseInterface
    {
        $payload = trim((string) $request->getBody());
        $data    = json_decode($payload, true);

        if (! is_array($data)) {
            return $response->withStatus(400)->write('Invalid payload');
        }

        $apiToken = trim($this->config['bepusdt_api_token']);

        if (! $this->verifySignature($data, $apiToken)) {
            return $response->withStatus(400)->write('Invalid signature');
        }

        // status 或 trade_status: 1=等待支付, 2=支付成功, 3=支付超时
        $status = (int) ($data['status'] ?? ($data['trade_status'] ?? 0));
        if ($status !== 2) {
            return $response->write('ok');
        }

        $tradeno = $data['order_id'] ?? '';
        if (! $tradeno) {
            return $response->withStatus(400)->write('Missing order_id');
        }

        $pl = (new Paylist())->where('tradeno', $tradeno)->first();
        if ($pl === null) {
            return $response->withStatus(400)->write('Paylist not found');
        }

        $actualAmount = isset($data['amount']) ? (float) $data['amount'] : null;

        $this->postPayment($tradeno, $actualAmount);

        // BEpusdt 官方要求回调成功必须返回 HTTP 200 且响应体为纯文本 "ok"
        return $response->write('ok');
    }

    /**
     * @throws Exception
     */
    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/bepusdt.tpl');
    }

    // ─────────────────────────── helpers ───────────────────────────

    /**
     * BEpusdt 签名算法：
     * 1. 过滤空值和 signature 自身
     * 2. 按键名字典序 (ASCII 升序) 排序
     * 3. 拼接 key=value&key=value (注意：不进行 urlencode)
     * 4. 末尾直接拼接 token 并 MD5 加密转小写
     */
    private function buildSignature(array $params, string $token): string
    {
        unset($params['signature']);

        ksort($params);

        $signStr = '';
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr = rtrim($signStr, '&');

        return strtolower(md5($signStr . $token));
    }

    private function verifySignature(array $data, string $token): bool
    {
        if (! $token) {
            return false;
        }

        $signature = $data['signature'] ?? '';
        if (! $signature) {
            return false;
        }

        unset($data['signature']);

        $expected = $this->buildSignature($data, $token);

        return hash_equals($expected, $signature);
    }
}
