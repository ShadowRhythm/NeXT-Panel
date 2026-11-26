<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Paylist;
use App\Services\Auth;
use App\Services\Exchange;
use App\Services\I18n;
use App\Services\View;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RedisException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Throwable;
use voku\helper\AntiXSS;
use function http_build_query;
use function is_array;
use function is_string;
use function json_decode;
use function parse_str;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function trim;

final class PayPal extends Base
{
    protected array $gateway_config;

    public function __construct()
    {
        $this->antiXss = new AntiXSS();
        $configs = Config::getClass('billing');

        $this->gateway_config = [
            'mode' => $configs['paypal_mode'],
            'sandbox' => [
                'client_id' => $configs['paypal_client_id'],
                'client_secret' => $configs['paypal_client_secret'],
                'app_id' => '',
            ],
            'live' => [
                'client_id' => $configs['paypal_client_id'],
                'client_secret' => $configs['paypal_client_secret'],
                'app_id' => '',
            ],
            'payment_action' => 'Sale',
            'currency' => $configs['paypal_currency'],
            'notify_url' => '',
            'locale' => $configs['paypal_locale'],
            'validate_ssl' => true,
        ];
    }

    public static function _name(): string
    {
        return 'paypal';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('paypal');
    }

    public static function _readableName(): string
    {
        return 'PayPal';
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

        $pl->gateway = self::_readableName();
        $pl->save();

        try {
            $exchange_amount = (new Exchange())->exchange(
                (float) $price,
                'CNY',
                Config::obtain('paypal_currency')
            );
        } catch (GuzzleException|RedisException) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_exchange_rate_failed', $locale),
            ]);
        }

        $order_data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => Config::obtain('paypal_currency'),
                        'value' => $exchange_amount,
                    ],
                    'invoice_id' => $pl->tradeno,
                ],
            ],
        ];

        try {
            $pp = new PayPalClient($this->gateway_config);
            $pp->getAccessToken();
            $order = $pp->createOrder($order_data);
        } catch (Throwable) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_paypal_api_error', $locale),
            ]);
        }

        return $response->withJson($order);
    }

    public function notify($request, $response, $args): ResponseInterface
    {
        $raw_body = (string) $request->getBody();
        $contentType = $request->getHeaderLine('Content-Type');

        // Check if incoming request is a JSON Webhook
        if (str_contains($contentType, 'application/json') || (str_starts_with(trim($raw_body), '{') && str_ends_with(trim($raw_body), '}'))) {
            $webhook_data = json_decode($raw_body, true);

            if (! is_array($webhook_data) || ! isset($webhook_data['event_type'])) {
                return $response->withStatus(400)->withJson(['ret' => 0, 'msg' => 'Invalid webhook payload']);
            }

            try {
                $pp = new PayPalClient($this->gateway_config);
                $pp->getAccessToken();

                $authAlgo = $request->getHeaderLine('PAYPAL-AUTH-ALGO');
                $certUrl = $request->getHeaderLine('PAYPAL-CERT-URL');
                $transmissionId = $request->getHeaderLine('PAYPAL-TRANSMISSION-ID');
                $transmissionSig = $request->getHeaderLine('PAYPAL-TRANSMISSION-SIG');
                $transmissionTime = $request->getHeaderLine('PAYPAL-TRANSMISSION-TIME');
                $webhookId = Config::obtain('paypal_webhook_id');

                $verified = false;

                if ($webhookId !== '' && $transmissionId !== '' && $transmissionSig !== '') {
                    $verifyResult = $pp->verifyWebHook([
                        'auth_algo' => $authAlgo,
                        'cert_url' => $certUrl,
                        'transmission_id' => $transmissionId,
                        'transmission_sig' => $transmissionSig,
                        'transmission_time' => $transmissionTime,
                        'webhook_id' => $webhookId,
                        'webhook_event' => $webhook_data,
                    ]);
                    $verified = ($verifyResult['verification_status'] ?? '') === 'SUCCESS';
                }

                if ($webhook_data['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
                    $resource = $webhook_data['resource'] ?? [];
                    $captureId = $resource['id'] ?? null;
                    $tradeNo = $resource['invoice_id'] ?? null;

                    // Query PayPal API directly for capture status if webhook signature couldn't be checked locally
                    if (! $verified && is_string($captureId) && $captureId !== '') {
                        $captureDetails = $pp->showCapturedPaymentDetails($captureId);
                        if (($captureDetails['status'] ?? '') === 'COMPLETED') {
                            $verified = true;
                            $tradeNo = $captureDetails['invoice_id'] ?? $tradeNo;
                        }
                    }

                    if ($verified && is_string($tradeNo) && $tradeNo !== '' && ($resource['status'] ?? '') === 'COMPLETED') {
                        $this->postPayment($tradeNo);
                        return $response->withStatus(204);
                    }
                }

                // Return 200 for other event types so PayPal does not continuously retry
                return $response->withStatus(200)->withJson(['ret' => 1, 'msg' => 'Event ignored']);
            } catch (Throwable) {
                return $response->withStatus(400)->withJson([
                    'ret' => 0,
                    'msg' => 'PayPal API Error',
                ]);
            }
        }

        // Handle PayPal IPN (Instant Payment Notification)
        $params = (array) ($request->getParsedBody() ?: $_POST);
        if (empty($params) && ! empty($raw_body)) {
            parse_str($raw_body, $params);
        }

        if (! empty($params)) {
            $paypalUrl = ($this->gateway_config['mode'] === 'sandbox')
                ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'
                : 'https://ipnpb.paypal.com/cgi-bin/webscr';

            $client = new Client();
            try {
                $postbackBody = 'cmd=_notify-validate&' . http_build_query($params);
                $res = $client->request('POST', $paypalUrl, [
                    'body' => $postbackBody,
                    'headers' => ['User-Agent' => 'SSPanel-PayPal-IPN'],
                    'timeout' => 15,
                ]);

                $resBody = trim($res->getBody()->__toString());
                if ($resBody === 'VERIFIED') {
                    $paymentStatus = $params['payment_status'] ?? '';
                    $tradeNo = $params['invoice'] ?? $params['custom'] ?? '';

                    if ($paymentStatus === 'Completed' && is_string($tradeNo) && $tradeNo !== '') {
                        $this->postPayment($tradeNo);
                        return $response->write('success');
                    }
                }
            } catch (Throwable) {
                return $response->write('failed');
            }
        }

        return $response->write('failed');
    }

    /**
     * @throws Exception
     */
    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/paypal.tpl');
    }
}
