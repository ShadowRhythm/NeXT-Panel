<?php

declare(strict_types=1);

namespace App\Services\Bot\Telegram;

use App\Models\Config;
use App\Models\User;
use App\Services\Cache;
use App\Services\I18n;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;
use function trim;

final class Telegram
{
    /**
     * @throws TelegramSDKException|GuzzleException
     */
    public static function process(RequestInterface $request): void
    {
        $bot = new Api(Config::obtain('telegram_token'));

        $bot->addCommands([
            new Commands\MyCommand(),
            new Commands\HelpCommand(),
            new Commands\MenuCommand(),
            new Commands\PingCommand(),
            new Commands\DcCommand(),
            new Commands\StartCommand(),
            new Commands\UnbindCommand(),
            new Commands\CheckinCommand(),
            new Commands\BindCommand(),
        ]);

        $bot->commandsHandler(true, $request);
        $bot->setConnectTimeOut(1);
        $update = $bot->getWebhookUpdate();

        try {
            if ($update->has('callback_query')) {
                new Callback($bot, $update->callbackQuery);
            } elseif ($update->has('message')) {
                new Message($bot, $update->message);
            }
        } catch (Throwable) {
            // ignore to avoid webhook 500 error
        }
    }

    public static function bindUserWithToken(int $telegram_user_id, string $token): string
    {
        $defaultLocale = Message::getLocale();
        $token = trim($token);
        if ($token === '') {
            return I18n::trans('bot.bind_empty_token', $defaultLocale);
        }

        try {
            $redis = (new Cache())->initRedis();
            $user_id = $redis->get('telegram_bind_token:' . $token);
        } catch (Throwable) {
            return I18n::trans('bot.bind_cache_error', $defaultLocale);
        }

        if (! $user_id) {
            return I18n::trans('bot.bind_token_expired', $defaultLocale);
        }

        $user = (new User())->find((int) $user_id);
        if ($user === null) {
            return I18n::trans('bot.bind_user_not_found', $defaultLocale);
        }

        $userLocale = Message::getLocale($user);

        $existing = (new User())->where('im_type', 4)->where('im_value', (string) $telegram_user_id)->first();
        if ($existing !== null) {
            if ((int) $existing->id === (int) $user->id) {
                return I18n::trans('bot.bind_already_this_account', $userLocale, ['email' => $user->email]);
            }

            return I18n::trans('bot.bind_telegram_used_by_other', $userLocale, ['email' => $existing->email]);
        }

        if ($user->im_type === 4 && $user->im_value !== '') {
            return I18n::trans('bot.bind_user_has_other_telegram', $userLocale, ['email' => $user->email]);
        }

        $user->im_type = 4;
        $user->im_value = (string) $telegram_user_id;
        $user->save();

        try {
            $redis->del('telegram_bind_token:' . $token);
            $redis->del('telegram_bind_user:' . $user->id);
        } catch (Throwable) {
            // Ignore
        }

        return I18n::trans('bot.bind_success', $userLocale, ['email' => $user->email]);
    }
}
