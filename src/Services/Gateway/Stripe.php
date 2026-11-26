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
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RedisException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;
use voku\helper\AntiXSS;
use function in_array;

final class Stripe extends Base
{
    public function __construct()
    {
        $this->antiXss = new AntiXSS();
    }

    public static function _name(): string
    {
        return 'stripe';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('stripe');
    }

    public static function _readableName(): string
    {
        return 'Stripe';
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

        if ($price < Config::obtain('stripe_min_recharge') ||
            $price > Config::obtain('stripe_max_recharge')
        ) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_price_out_of_range', $locale),
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

        $stripe_currency = Config::obtain('stripe_currency');

        try {
            $exchange_amount = (new Exchange())->exchange((float) $price, 'CNY', $stripe_currency);
        } catch (GuzzleException|RedisException) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_exchange_rate_failed', $locale),
            ]);
        }
        // https://docs.stripe.com/currencies?presentment-currency=US#zero-decimal
        // UGX 属 zero-decimal，但 charge 仍要求两位表示（5 UGX 传 500），故从下表移除；ISK 有同样要求，本就不在表内
        if (! in_array(
            $stripe_currency,
            ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW',
                'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
            ]
        )) {
            $exchange_amount *= 100;
        }

        $stripe = new StripeClient(Config::obtain('stripe_api_key'));
        $session = null;

        try {
            $session = $stripe->checkout->sessions->create([
                'customer_email' => $user->email,
                'metadata' => [
                    'trade_no' => $pl->tradeno,
                ],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => Config::obtain('stripe_currency'),
                            'product_data' => [
                                'name' => 'Invoice #' . $invoice_id,
                            ],
                            'unit_amount' => (int)($exchange_amount * 1.0325 + 20),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'payment_intent_data' => [
                    'metadata' => [
                        'trade_no' => $pl->tradeno,
                    ],
                ],
                'success_url' => $_ENV['baseUrl'] . '/user/invoice/' . $invoice_id . '/view',
                'cancel_url' => $_ENV['baseUrl'] . '/user/invoice/' . $invoice_id . '/view',
            ]);
        } catch (ApiErrorException) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.gateway_stripe_api_error', $locale),
            ]);
        }

        return $response->withHeader('HX-Redirect', $session->url);
    }

    public function notify(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $raw_body = (string) $request->getBody();
        $sig_header = $request->getHeaderLine('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $raw_body,
                $sig_header,
                Config::obtain('stripe_endpoint_secret')
            );
        } catch (UnexpectedValueException) {
            return $response->withStatus(400)->withJson([
                'ret' => 0,
                'msg' => 'Unexpected Value error',
            ]);
        } catch (SignatureVerificationException) {
            return $response->withStatus(400)->withJson([
                'ret' => 0,
                'msg' => 'Signature Verification error',
            ]);
        }

        $trade_no = null;

        if ($event->type === 'payment_intent.succeeded') {
            $payment_intent = $event->data->object;
            if ($payment_intent->status === 'succeeded') {
                $trade_no = $payment_intent->metadata->trade_no ?? null;
            }
        } elseif ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            if ($session->payment_status === 'paid') {
                $trade_no = $session->metadata->trade_no ?? null;
            }
        }

        if (is_string($trade_no) && $trade_no !== '') {
            $this->postPayment($trade_no);

            return $response->withStatus(204);
        }

        // Return 200 for other event types so Stripe does not mark webhook delivery as failing
        return $response->withStatus(200)->withJson([
            'ret' => 1,
            'msg' => 'Event ignored',
        ]);
    }

    /**
     * @throws Exception
     */
    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/stripe.tpl');
    }
}
