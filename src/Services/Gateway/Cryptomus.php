<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Paylist;
use App\Services\Auth;
use App\Services\Gateway\Cryptomus\Payment as CryptomusPayment;
use App\Services\View;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use voku\helper\AntiXSS;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;
use function trim;

final class Cryptomus extends Base
{
    /**
     * @var array
     */
    protected array $cryptomus = [];

    public function __construct()
    {
        $this->antiXss = new AntiXSS();

        $this->cryptomus['cryptomus_api_key'] = Config::obtain('cryptomus_api_key');
        $this->cryptomus['cryptomus_uuid'] = Config::obtain('cryptomus_uuid');
        $this->cryptomus['cryptomus_subtract'] = Config::obtain('cryptomus_subtract');
        $this->cryptomus['cryptomus_lifetime'] = Config::obtain('cryptomus_lifetime');
        $this->cryptomus['cryptomus_currency'] = Config::obtain('cryptomus_currency');
    }

    public static function _name(): string
    {
        return 'cryptomus';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('cryptomus');
    }

    public static function _readableName(): string
    {
        return 'Cryptomus';
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     * @throws Exception
     */
    public function purchase(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = Auth::getUser();
        if ($user === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Please login first',
            ]);
        }

        $invoiceId = $this->antiXss->xss_clean($request->getParam('invoice_id'));
        $invoice = (new Invoice())->find($invoiceId);

        if ($invoice === null || $invoice->user_id !== $user->id) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Invoice not found',
            ]);
        }

        if ($invoice->status !== 'unpaid' && $invoice->status !== 'partially_paid') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Invoice is not payable',
            ]);
        }

        $price = $invoice->price;

        if ($price <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '非法的金额',
            ]);
        }

        $redir = self::sanitizeReturnUrl($this->antiXss->xss_clean($request->getParam('redir')));

        $pl = (new Paylist())->where('invoice_id', $invoiceId)->first();

        if ($pl === null) {
            $pl = new Paylist();
            $pl->userid = $user->id;
            $pl->total = $price;
            $pl->invoice_id = $invoiceId;
            $pl->tradeno = self::generateGuid();
        }

        $pl->gateway = self::_readableName();
        $pl->save();

        $paymentData = [
            'amount' => (string) $price,
            'currency' => $this->cryptomus['cryptomus_currency'] ?? 'CNY',
            'order_id' => 'sspanel_' . $invoiceId . '_' . time(),
            'url_return' => $redir,
            'url_callback' => self::getCallbackUrl(),
            'lifetime' => $this->cryptomus['cryptomus_lifetime'] ?? '3600',
            'subtract' => $this->cryptomus['cryptomus_subtract'] ?? '0',
            'plugin_name' => 'sspanel:2024.1',
            'additional_data' => json_encode(['tradeno' => $pl->tradeno]),
        ];

        $paymentInstance = $this->getPayment();

        try {
            $payment = $paymentInstance->create($paymentData);
        } catch (\Exception $exception) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '请求支付失败: ' . $exception->getMessage(),
            ]);
        }

        $payUrl = $payment['url'] ?? null;
        if (! self::isValidExternalRedirectUrl($payUrl)) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '请求支付失败，网关返回地址无效',
            ]);
        }

        return $response->withHeader('HX-Redirect', $payUrl)->withJson([
            'ret' => 1,
            'msg' => '订单发起成功，正在跳转到支付页面...',
        ]);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     *
     * @return ResponseInterface
     */
    public function notify($request, $response, $args): ResponseInterface
    {
        $payload = trim((string) $request->getBody());
        $data = json_decode($payload, true);

        if (! is_array($data) || ! $this->hashEqual($data)) {
            return $response->withJson(['state' => 'fail', 'msg' => 'Sign is not valid']);
        }

        $additionalData = isset($data['additional_data']) ? json_decode($data['additional_data'], true) : [];
        $tradeno = $additionalData['tradeno'] ?? null;

        if (! is_string($tradeno) || $tradeno === '') {
            return $response->withJson(['state' => 'fail', 'msg' => 'Trade number not found']);
        }

        // Only accept paid or paid_over. DO NOT blindly accept wrong_amount (underpayment)!
        $status = $data['status'] ?? '';
        $isFinal = ! empty($data['is_final']);
        $actualAmount = isset($data['merchant_amount']) && is_numeric($data['merchant_amount']) ? (float) $data['merchant_amount'] : null;

        $pl = (new Paylist())->where('tradeno', $tradeno)->first();
        if ($pl === null) {
            return $response->withJson(['state' => 'fail', 'msg' => 'Paylist not found']);
        }

        $isValidPayment = false;
        if ($isFinal && ($status === 'paid' || $status === 'paid_over')) {
            $isValidPayment = true;
        } elseif ($isFinal && $status === 'wrong_amount' && $actualAmount !== null && $actualAmount >= (float) $pl->total - 0.01) {
            $isValidPayment = true;
        }

        if ($isValidPayment) {
            $this->postPayment($tradeno, $actualAmount);

            return $response->withJson([
                'ret' => 1,
                'msg' => '支付成功',
            ]);
        }

        return $response->withJson(['state' => 'fail', 'msg' => 'Payment failed or underpaid']);
    }

    /**
     * @throws Exception
     */
    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/cryptomus.tpl');
    }

    /**
     * @return CryptomusPayment
     *
     * @throws \Exception
     */
    private function getPayment(): CryptomusPayment
    {
        $merchantUuid = trim($this->cryptomus['cryptomus_uuid']);
        $paymentKey = trim($this->cryptomus['cryptomus_api_key']);

        if (! $merchantUuid || ! $paymentKey) {
            throw new Exception('Please fill UUID and API key');
        }

        return new CryptomusPayment($paymentKey, $merchantUuid);
    }

    /**
     * @param $data
     *
     * @return bool
     */
    private function hashEqual($data): bool
    {
        $paymentKey = trim($this->cryptomus['cryptomus_api_key']);

        if (! $paymentKey) {
            return false;
        }

        $signature = $data['sign'] ?? '';
        if (! $signature) {
            return false;
        }

        unset($data['sign']);

        $hash = md5(base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE)) . $paymentKey);
        if (! hash_equals($hash, $signature)) {
            return false;
        }

        return true;
    }
}
