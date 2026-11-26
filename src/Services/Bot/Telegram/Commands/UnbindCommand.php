<?php

declare(strict_types=1);

namespace App\Services\Bot\Telegram\Commands;

use App\Models\Config;
use App\Services\Bot\Telegram\Message;
use App\Services\I18n;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function array_splice;
use function explode;
use function trim;
use const PHP_EOL;

/**
 * Class UnbindCommand.
 */
final class UnbindCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected string $name = 'unbind';

    /**
     * @var string Command Description
     */
    protected string $description = '[私聊] 解除账户绑定';

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        $update = $this->update;
        $message = $update->message;
        $send_user = [
            'id' => $message->from->id,
        ];
        $user = Message::getUser($send_user['id']);

        if ($message->chat->type === 'private') {
            // 发送 '输入中' 会话状态
            $this->replyWithChatAction(['action' => Actions::TYPING]);

            if ($user === null) {
                // 回送信息
                $this->replyWithMessage(
                    [
                        'text' => I18n::trans('bot.user_not_bind', Message::getLocale()),
                        'parse_mode' => 'Markdown',
                    ]
                );
                return;
            }
            $locale = Message::getLocale($user);
            // 消息内容
            $message_text = explode(' ', trim($message->text));
            $message_key = array_splice($message_text, -1)[0];
            $text = '';

            if ($message_key === $user->email) {
                if ($user->unbindIM()) {
                    $text = I18n::trans('bot.unbind_success', $locale);
                } else {
                    $text = I18n::trans('bot.unbind_failed', $locale);
                }
                // 回送信息
                $this->replyWithMessage(
                    [
                        'text' => $text,
                        'parse_mode' => 'Markdown',
                    ]
                );

                return;
            }

            if ($message_key !== '') {
                $text = I18n::trans('bot.unbind_email_mismatch', $locale);
            }

            if ($message_key === '/unbind') {
                $text = $this->sendText($locale);
            }

            // 回送信息
            $this->replyWithMessage(
                [
                    'text' => $text,
                    'parse_mode' => 'Markdown',
                ]
            );
        }
    }

    private function sendText(string $locale): string
    {
        $text = I18n::trans('bot.unbind_usage_tip', $locale);

        if (Config::obtain('telegram_unbind_kick_member')) {
            $text .= PHP_EOL . PHP_EOL . I18n::trans('bot.unbind_kick_warning', $locale);
        }

        return $text;
    }
}
