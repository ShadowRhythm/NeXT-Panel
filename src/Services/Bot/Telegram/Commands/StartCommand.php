<?php

declare(strict_types=1);

namespace App\Services\Bot\Telegram\Commands;

use App\Models\Config;
use App\Services\Bot\Telegram\Message;
use App\Services\Bot\Telegram\Telegram;
use App\Services\I18n;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use function explode;
use function in_array;
use function trim;

/**
 * Class StartCommand.
 */
final class StartCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected string $name = 'start';

    /**
     * @var string Command Description
     */
    protected string $description = '[群组/私聊] Bot 初始命令';

    public function handle(): void
    {
        $update = $this->update;
        $message = $update->message;

        if ($message->chat->type === 'private') {
            // 发送 '输入中' 会话状态
            $this->replyWithChatAction(['action' => Actions::TYPING]);

            $parts = explode(' ', trim((string) $message->text), 2);
            $token = isset($parts[1]) ? trim($parts[1]) : '';

            if ($token !== '') {
                $reply = Telegram::bindUserWithToken((int) $message->from->id, $token);
                $this->replyWithMessage(
                    [
                        'text' => $reply,
                        'parse_mode' => 'Markdown',
                    ]
                );
                return;
            }

            // 回送信息
            $user = Message::getUser((int) $message->from->id);
            $this->replyWithMessage(
                [
                    'text' => I18n::trans('bot.start_help_prompt', Message::getLocale($user)),
                    'parse_mode' => 'Markdown',
                ]
            );
        } elseif (in_array($message->chat->type, ['group', 'supergroup']) && ! Config::obtain('telegram_group_quiet')) {
            // 发送 '输入中' 会话状态
            $this->replyWithChatAction(['action' => Actions::TYPING]);
            // 回送信息
            $this->replyWithMessage(
                [
                    'text' => '?',
                    'parse_mode' => 'Markdown',
                    'reply_to_message_id' => $message->messageId,
                ]
            );
        }
    }
}
