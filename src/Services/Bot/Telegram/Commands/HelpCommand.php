<?php

declare(strict_types=1);

namespace App\Services\Bot\Telegram\Commands;

use App\Models\Config;
use App\Services\Bot\Telegram\Message;
use App\Services\I18n;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use function in_array;
use function preg_match;
use const PHP_EOL;

/**
 * Class HelpCommand.
 */
final class HelpCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected string $name = 'help';

    /**
     * @var string Command Description
     */
    protected string $description = '[群组/私聊] 系统中可用的所有指令';

    public function handle(): void
    {
        $update = $this->update;
        $message = $update->message;

        if (in_array($message->chat->type, ['group', 'supergroup']) && Config::obtain('telegram_group_quiet')) {
            return;
        }

        if (! preg_match('/^\/help\s?(@' . Config::obtain('telegram_bot') . ')?.*/i', $message->text) &&
            ! Config::obtain('help_any_command')) {
            return;
        }

        $this->replyWithChatAction(['action' => Actions::TYPING]);
        $user = Message::getUser((int) $message->from->id);
        $locale = Message::getLocale($user);
        $commands = $this->telegram->getCommands();
        $text = I18n::trans('bot.help_title', $locale);
        $text .= PHP_EOL . PHP_EOL;

        foreach ($commands as $name => $handler) {
            $descKey = 'bot.cmd_desc_' . $name;
            $translatedDesc = I18n::trans($descKey, $locale);
            $desc = ($translatedDesc !== $descKey && $translatedDesc !== '') ? $translatedDesc : $handler->getDescription();
            $text .= '/' . $name . PHP_EOL . '`    - ' . $desc . '`' . PHP_EOL;
        }

        $this->replyWithMessage(
            [
                'text' => $text,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => false,
                'reply_to_message_id' => $message->messageId,
                'reply_markup' => null,
            ]
        );
    }
}
