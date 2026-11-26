<?php

declare(strict_types=1);

namespace App\Services\Bot\Telegram;

use App\Models\Config;
use App\Models\User;
use App\Services\I18n;
use GuzzleHttp\Exception\GuzzleException;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function array_merge;
use function implode;
use function in_array;
use function json_decode;
use function str_replace;
use const PHP_EOL;

final class Message
{
    /**
     * Bot
     */
    private Api $bot;

    /**
     * 消息会话 ID
     */
    private int $chat_id;

    /**
     * 触发源信息
     */
    private \Telegram\Bot\Objects\Message $message;

    /**
     * 触发源信息 ID
     */
    private int $message_id;

    /**
     * @throws TelegramSDKException|GuzzleException
     */
    public function __construct(Api $bot, \Telegram\Bot\Objects\Message $message)
    {
        $this->bot = $bot;
        $this->chat_id = $message->chat->id;
        $this->message = $message;
        $this->message_id = $message->messageId;

        if ($this->message->newChatMembers !== null) {
            $this->newChatParticipant();
        }
    }

    public static function getLocale(?User $user = null): string
    {
        if ($user !== null && ! empty($user->locale) && I18n::isValidLocale($user->locale)) {
            return $user->locale;
        }

        $default = $_ENV['locale'] ?? 'en_US';

        return I18n::isValidLocale($default) ? $default : 'en_US';
    }

    /**
     * 回复讯息 | 默认已添加 chat_id 和 message_id
     *
     * @param array $send_message
     *
     * @throws TelegramSDKException
     */
    public function replyWithMessage(array $send_message): void
    {
        $send_message = array_merge(
            [
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id,
            ],
            $send_message
        );

        $this->bot->sendMessage($send_message);
    }

    /**
     * 入群检测
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function newChatParticipant(): void
    {
        $new_chat_member = $this->message->newChatMembers[0];

        $member = [
            'id' => $new_chat_member->id,
            'name' => $new_chat_member->firstName . ' ' . $new_chat_member->lastName,
        ];

        if ($new_chat_member->username === Config::obtain('telegram_bot')) {
            // 机器人加入新群组
            if (! Config::obtain('allow_to_join_new_groups')
                &&
                ! in_array($this->chat_id, json_decode(Config::obtain('group_id_allowed_to_join')))) {
                // 退群
                $this->bot->leaveChat(
                    [
                        'chat_id' => $this->chat_id,
                    ]
                );
            } else {
                $this->replyWithMessage(
                    [
                        'text' => I18n::trans('bot.bot_joined_group', self::getLocale()),
                    ]
                );
            }
        } else {
            // 新成员加入群组
            $new_user = self::getUser($member['id']);

            if (Config::obtain('telegram_group_bound_user')
                &&
                $this->chat_id === Config::obtain('telegram_chatid')
                &&
                $new_user === null
                &&
                ! $new_chat_member->isBot
            ) {
                $this->replyWithMessage(
                    [
                        'text' => I18n::trans('bot.member_not_bound_removed', self::getLocale(), ['name' => $member['name']]),
                    ]
                );

                $this->bot->banChatMember(
                    [
                        'chat_id' => $this->chat_id,
                        'user_id' => $member['id'],
                    ]
                );

                return;
            }

            if (Config::obtain('enable_welcome_message')) {
                $locale = self::getLocale($new_user);
                $isPaid = $new_user !== null && $new_user->class > 0;
                $text = $isPaid
                    ? I18n::trans('bot.user_join_welcome_paid', $locale)
                    : I18n::trans('bot.user_join_welcome_free', $locale);

                $this->replyWithMessage(
                    [
                        'text' => str_replace(
                            [
                                '%user_name%',
                                '%user_class%',
                            ],
                            [
                                $member['name'],
                                $new_user->class ?? 0,
                            ],
                            $text
                        ),
                    ]
                );
            }
        }
    }

    /**
     * 用户的流量使用讯息
     */
    public static function getUserTrafficInfo(User $user): string
    {
        $locale = self::getLocale($user);
        $text = [
            I18n::trans('bot.traffic_status_title', $locale),
            '',
            I18n::trans('bot.traffic_used_today', $locale, [
                'percent' => $user->todayUsedTrafficPercent(),
                'traffic' => $user->todayUsedTraffic(),
            ]),
            I18n::trans('bot.traffic_used_before', $locale, [
                'percent' => $user->lastUsedTrafficPercent(),
                'traffic' => $user->lastUsedTraffic(),
            ]),
            I18n::trans('bot.traffic_remaining', $locale, [
                'percent' => $user->unusedTrafficPercent(),
                'traffic' => $user->unusedTraffic(),
            ]),
        ];

        return implode(PHP_EOL, $text);
    }

    /**
     * 用户基本讯息
     */
    public static function getUserInfo(User $user): string
    {
        $locale = self::getLocale($user);
        $speedLimit = $user->node_speedlimit > 0
            ? $user->node_speedlimit . 'Mbps'
            : I18n::trans('bot.info_unlimited', $locale);

        $text = [
            I18n::trans('bot.info_balance', $locale, ['balance' => $user->money]),
            I18n::trans('bot.info_speed_limit', $locale, ['limit' => $speedLimit]),
            I18n::trans('bot.info_last_used', $locale, ['time' => $user->lastUseTime($locale)]),
            I18n::trans('bot.info_expire_time', $locale, ['time' => $user->class_expire]),
        ];

        return implode(PHP_EOL, $text);
    }

    /**
     * 获取用户或管理的尊称
     */
    public static function getUserTitle(User $user): string
    {
        $locale = self::getLocale($user);

        if ($user->class > 0) {
            return I18n::trans('bot.title_paid', $locale);
        }

        return I18n::trans('bot.title_free', $locale);
    }

    /**
     * 搜索用户
     *
     * @param int $value  搜索值
     */
    public static function getUser(int $value): null|User
    {
        return (new User())->where('im_type', 4)->where('im_value', $value)->first();
    }
}
