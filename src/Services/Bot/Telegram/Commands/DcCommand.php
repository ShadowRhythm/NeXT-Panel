<?php

declare(strict_types=1);

namespace App\Services\Bot\Telegram\Commands;

use App\Services\Bot\Telegram\Message;
use App\Services\I18n;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function implode;
use function preg_match;
use const PHP_EOL;

final class DcCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected string $name = 'dc';

    /**
     * @var string Command Description
     */
    protected string $description = '[私聊] 获取用户当前所在的 DC';

    /**
     * @throws TelegramSDKException
     * @throws GuzzleException
     */
    public function handle(): void
    {
        $update = $this->update;
        $message = $update->message;
        $chat_id = $message->chat->id;

        if ($message->chat->type === 'private') {
            // 发送 '输入中' 会话状态
            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $user = Message::getUser((int) $message->from->id);
            $locale = Message::getLocale($user);

            $text = [
                I18n::trans('bot.dc_no_photo', $locale),
            ];

            $profile_photos = $this->telegram->getUserProfilePhotos([
                'user_id' => $message->from->id,
                'limit' => 1,
            ]);

            if (isset($profile_photos['total_count']) &&
                $profile_photos['total_count'] > 0 &&
                $message->from->username !== null
            ) {
                $client = new Client();

                $t_me_data = $client->get('https://t.me/' . $message->from->username)->getBody()->getContents();
                preg_match(
                    '/<img class="tgme_page_photo_image" src="([^<>]+)">/',
                    $t_me_data,
                    $profile_photo_url
                );
                preg_match(
                    '/https:\/\/cdn([1-5])\.cdn-telegram\.org\//',
                    $profile_photo_url[1],
                    $dc
                );

                $text = [
                    I18n::trans('bot.dc_location', $locale, ['dc' => $dc[1]]),
                ];
            }
            // 回送信息
            $this->replyWithMessage(
                [
                    'text' => implode(PHP_EOL, $text),
                    'parse_mode' => 'HTML',
                ]
            );
        }
    }
}
