<?php

declare(strict_types=1);

namespace App\Services\IM;

use App\Models\Config;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function microtime;
use function sleep;
use function str_contains;
use function str_replace;
use function strip_tags;
use function usleep;

final class Telegram extends Base
{
    private Api $bot;
    private static float $lastSendTime = 0.0;

    /**
     * @throws TelegramSDKException
     */
    public function __construct()
    {
        $this->bot = new Api(Config::obtain('telegram_token'));
    }

    /**
     * Execute sending action with rate limit throttling and 429 retry
     *
     * @throws TelegramSDKException
     */
    private function executeWithThrottle(callable $action): mixed
    {
        // Enforce at least 1.0s gap between consecutive sends to satisfy Telegram group rate limit (1 msg/sec)
        $now = microtime(true);
        $elapsed = $now - self::$lastSendTime;
        if ($elapsed < 1.0) {
            usleep((int) ((1.0 - $elapsed) * 1000000));
        }

        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $res = $action();
                self::$lastSendTime = microtime(true);
                return $res;
            } catch (TelegramSDKException $e) {
                $errMsg = $e->getMessage();
                $isRateLimit = str_contains($errMsg, '429') || str_contains($errMsg, 'Too Many Requests');
                if ($attempt < $maxRetries && ($isRateLimit || str_contains($errMsg, 'timeout') || str_contains($errMsg, 'timed out'))) {
                    sleep(2);
                    continue;
                }
                throw $e;
            }
        }

        return null;
    }

    /**
     * @throws TelegramSDKException
     */
    public function send($to, $msg = ''): void
    {
        $msg = str_replace(
            [
                '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!',
            ],
            [
                '\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!',
            ],
            $msg
        );

        $sendMessage = [
            'chat_id' => $to,
            'text' => $msg,
            'parse_mode' => 'MarkdownV2',
            'disable_web_page_preview' => false,
            'reply_to_message_id' => null,
            'reply_markup' => null,
        ];

        $this->executeWithThrottle(fn () => $this->bot->sendMessage($sendMessage));
    }

    /**
     * @throws TelegramSDKException
     */
    public function sendHtml(int $to = 0, string $msg = ''): void
    {
        $sendMessage = [
            'chat_id' => $to,
            'text' => strip_tags(
                $msg,
                ['b', 'strong', 'i', 'em', 'u', 'ins', 's', 'strike','del', 'span','tg-spoiler', 'a', 'tg-emoji',
                    'code', 'pre', 'blockquote',
                ]
            ),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
            'reply_to_message_id' => null,
            'reply_markup' => null,
        ];

        $this->executeWithThrottle(fn () => $this->bot->sendMessage($sendMessage));
    }

    /**
     * @throws TelegramSDKException
     */
    public function banGroupMember(int $user_id): void
    {
        $this->bot->banChatMember(
            [
                'chat_id' => Config::obtain('telegram_chatid'),
                'user_id' => $user_id,
            ]
        );
    }

    /**
     * @throws TelegramSDKException
     */
    public function unbanGroupMember(int $user_id): void
    {
        $this->bot->unbanChatMember(
            [
                'chat_id' => Config::obtain('telegram_chatid'),
                'user_id' => $user_id,
                'only_if_banned' => true,
            ]
        );
    }
}
