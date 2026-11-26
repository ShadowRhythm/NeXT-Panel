<?php

declare(strict_types=1);

namespace App\Services\Bot\Telegram\Commands;

use App\Services\Bot\Telegram\Message;
use App\Services\Bot\Telegram\Telegram;
use App\Services\I18n;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use function explode;
use function trim;

/**
 * Class BindCommand.
 */
final class BindCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected string $name = 'bind';

    /**
     * @var string Command Description
     */
    protected string $description = '[私聊] 绑定账户';

    public function handle(): void
    {
        $update = $this->update;
        $message = $update->message;

        if ($message->chat->type === 'private') {
            $this->replyWithChatAction(['action' => Actions::TYPING]);

            $parts = explode(' ', trim((string) $message->text), 2);
            $token = isset($parts[1]) ? trim($parts[1]) : '';

            if ($token === '') {
                $user = Message::getUser((int) $message->from->id);
                $this->replyWithMessage(
                    [
                        'text' => I18n::trans('bot.bind_usage_tip', Message::getLocale($user)),
                        'parse_mode' => 'Markdown',
                    ]
                );
                return;
            }

            $reply = Telegram::bindUserWithToken((int) $message->from->id, $token);

            $this->replyWithMessage(
                [
                    'text' => $reply,
                    'parse_mode' => 'Markdown',
                ]
            );
        }
    }
}
