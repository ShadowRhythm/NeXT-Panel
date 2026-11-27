<?php

declare(strict_types=1);

namespace App\Services\Bot\Telegram;

use App\Models\Config;
use App\Models\InviteCode;
use App\Models\LoginIp;
use App\Models\OnlineLog;
use App\Models\Payback;
use App\Models\SubscribeLog;
use App\Models\User;
use App\Services\I18n;
use App\Services\Reward;
use App\Services\Subscribe;
use App\Utils\Tools;
use GuzzleHttp\Exception\GuzzleException;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery;
use Throwable;
use function array_chunk;
use function array_merge;
use function end;
use function explode;
use function implode;
use function in_array;
use function is_null;
use function json_encode;
use function time;
use const PHP_EOL;

final class Callback
{
    /**
     * Bot
     */
    private Api $bot;

    /**
     * 触发用户
     */
    private null|User $user;

    /**
     * 触发用户TG信息
     */
    private array $trigger_user;

    /**
     * 回调
     */
    private CallbackQuery $callback;

    /**
     * 回调数据内容
     */
    private ?string $callback_data;

    /**
     * 消息会话 ID
     */
    private int $chat_id;

    /**
     * 触发源信息 ID
     */
    private int $message_id;

    /**
     * 源消息是否可编辑
     */
    private bool $allow_edit_message;

    /**
     * @throws TelegramSDKException|GuzzleException
     */
    public function __construct(Api $bot, CallbackQuery $callback)
    {
        $this->bot = $bot;

        $this->trigger_user = [
            'id' => $callback->from->id,
            'name' => $callback->from->firstName . ' ' . $callback->from->lastName,
            'username' => $callback->from->username,
        ];

        $this->user = Message::getUser($this->trigger_user['id']);
        $this->chat_id = $callback->message->chat->id;
        $this->callback = $callback;
        $this->message_id = $callback->message->messageId;
        $this->callback_data = $callback->data;
        $this->allow_edit_message = time() < $callback->message->date + 172800;

        if ($this->chat_id < 0 && Config::obtain('telegram_group_quiet')) {
            // 群组中不回应
            return;
        }

        if (str_starts_with($this->callback_data, 'user.')) {
            // 用户相关
            $this->userCallback();
        }
    }

    /**
     * 响应回调查询 | 默认已添加 chat_id 和 message_id
     *
     * @param array $send_message
     *
     * @throws TelegramSDKException|GuzzleException
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

        try {
            $this->answerCallbackQuery([]);
        } catch (Throwable) {
            // ignore
        }

        try {
            if ($this->allow_edit_message) {
                $this->bot->editMessageText($send_message);
            } else {
                $this->bot->sendMessage($send_message);
            }
        } catch (TelegramSDKException $e) {
            if (str_contains($e->getMessage(), 'message is not modified')) {
                return;
            }
            try {
                $this->bot->sendMessage($send_message);
            } catch (Throwable) {
                // ignore
            }
        } catch (Throwable) {
            // ignore
        }
    }

    /**
     * 响应回调查询 | 默认已添加 callback_query_id
     *
     * <code>
     * [
     *  'text'       => '',
     *  'show_alert' => false
     * ]
     * </code>
     *
     * @param array $send_message
     *
     * @throws TelegramSDKException
     */
    public function answerCallbackQuery(array $send_message): void
    {
        $send_message = array_merge(
            [
                'callback_query_id' => $this->callback->getId(),
                'show_alert' => false,
            ],
            $send_message
        );

        $this->bot->answerCallbackQuery($send_message);
    }

    public static function getUserIndexKeyboard($user): array
    {
        $locale = Message::getLocale($user);
        $checkin = (! $user->isAbleToCheckin()
            ? I18n::trans('bot.btn_checked_in', $locale)
            : I18n::trans('bot.btn_checkin', $locale));

        $keyboard = [
            [
                [
                    'text' => I18n::trans('bot.btn_user_center', $locale),
                    'callback_data' => 'user.center',
                ],
                [
                    'text' => I18n::trans('bot.btn_edit_profile', $locale),
                    'callback_data' => 'user.edit',
                ],
            ],
            [
                [
                    'text' => I18n::trans('bot.btn_sub_center', $locale),
                    'callback_data' => 'user.subscribe',
                ],
                [
                    'text' => I18n::trans('bot.btn_share_plan', $locale),
                    'callback_data' => 'user.invite',
                ],
            ],
            [
                [
                    'text' => $checkin,
                    'callback_data' => 'user.checkin.' . $user->im_value,
                ],
            ],
        ];

        $text = Message::getUserInfo($user);

        return [
            'text' => $text,
            'keyboard' => $keyboard,
        ];
    }

    /**
     * 用户相关回调数据处理
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userCallback(): void
    {
        if ($this->user === null) {
            $this->answerCallbackQuery([
                'text' => I18n::trans('bot.callback_not_bound', Message::getLocale()),
                'show_alert' => true,
            ]);
            return;
        }

        $locale = Message::getLocale($this->user);
        $CallbackDataExplode = explode('|', $this->callback_data);
        $Operate = explode('.', $CallbackDataExplode[0]);
        $op_1 = $Operate[1];

        switch ($op_1) {
            case 'edit':
                // 资料编辑
                $this->userEdit();
                break;
            case 'subscribe':
                // 订阅中心
                $this->userSubscribe();
                break;
            case 'invite':
                // 分享计划
                $this->userInvite();
                break;
            case 'checkin':
                // 签到
                if ((int) $Operate[2] !== $this->trigger_user['id']) {
                    $this->answerCallbackQuery([
                        'text' => I18n::trans('bot.callback_other_account', $locale),
                        'show_alert' => true,
                    ]);
                }
                $this->userCheckin();
                break;
            case 'center':
                // 用户中心
                $this->userCenter();
                break;
            default:
                // 用户首页
                $temp = self::getUserIndexKeyboard($this->user);

                $this->replyWithMessage([
                    'text' => $temp['text'],
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ]);
        }
    }

    public function getUserCenterKeyboard(): array
    {
        $locale = Message::getLocale($this->user);
        $text = Message::getUserTrafficInfo($this->user);

        $keyboard = [
            [
                [
                    'text' => I18n::trans('bot.btn_login_log', $locale),
                    'callback_data' => 'user.center.login_log',
                ],
                [
                    'text' => I18n::trans('bot.btn_online_ip', $locale),
                    'callback_data' => 'user.center.usage_log',
                ],
            ],
            [
                [
                    'text' => I18n::trans('bot.btn_rebate_log', $locale),
                    'callback_data' => 'user.center.rebate_log',
                ],
                [
                    'text' => I18n::trans('bot.btn_sub_log', $locale),
                    'callback_data' => 'user.center.subscribe_log',
                ],
            ],
            [
                [
                    'text' => I18n::trans('bot.btn_main_menu', $locale),
                    'callback_data' => 'user.index',
                ],
            ],
        ];

        return [
            'text' => $text,
            'keyboard' => $keyboard,
        ];
    }

    /**
     * 用户中心
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userCenter(): void
    {
        $locale = Message::getLocale($this->user);
        $back = [
            [
                [
                    'text' => I18n::trans('bot.btn_main_menu', $locale),
                    'callback_data' => 'user.index',
                ],
                [
                    'text' => I18n::trans('bot.btn_prev_page', $locale),
                    'callback_data' => 'user.center',
                ],
            ],
        ];

        $CallbackDataExplode = explode('|', $this->callback_data);
        $Operate = explode('.', $CallbackDataExplode[0]);
        $OpEnd = end($Operate);

        switch ($OpEnd) {
            case 'login_log':
                // 登录记录
                $total = (new LoginIp())->where('userid', $this->user->id)
                    ->where('type', '=', 0)
                    ->orderBy('datetime', 'desc')
                    ->take(10)
                    ->get();
                $text = '<strong>' . I18n::trans('bot.recent_login_title', $locale) . '</strong>' . PHP_EOL . PHP_EOL;

                foreach ($total as $single) {
                    $text .= $single->ip . ' - ' . Tools::getIpLocation($single->ip) . PHP_EOL;
                }

                $text .= PHP_EOL . '<strong>' . I18n::trans('bot.geoip_notice', $locale) . '</strong>' . PHP_EOL;

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $back,
                        ]
                    ),
                ];

                break;
            case 'usage_log':
                // 使用记录
                $logs = (new OnlineLog())->where('user_id', $this->user->id)
                    ->where('last_time', '>', time() - 90)
                    ->where('kicked_at', 0)
                    ->orderByDesc('last_time')
                    ->get('ip');
                $text = '<strong>' . I18n::trans('bot.online_ip_title', $locale) . '</strong>' . PHP_EOL . PHP_EOL;

                foreach ($logs as $log) {
                    $ip = $log->ip();
                    $text .= $ip . ' - ' . Tools::getIpLocation($ip) . PHP_EOL;
                }

                $text .= PHP_EOL . '<strong>' . I18n::trans('bot.geoip_notice', $locale) . '</strong>' . PHP_EOL;

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $back,
                        ]
                    ),
                ];

                break;
            case 'rebate_log':
                // 返利记录
                $paybacks = (new Payback())->where('ref_by', $this->user->id)->orderBy('datetime', 'desc')->take(10)->get();
                $text = '<strong>' . I18n::trans('bot.recent_rebate_title', $locale) . '</strong>' . PHP_EOL . PHP_EOL;

                foreach ($paybacks as $payback) {
                    $text .= '<code>#' . $payback->id .
                        '：' . ($payback->user() !== null ? $payback->user()->user_name : I18n::trans('bot.user_deleted', $locale)) . '：' .
                        $payback->ref_get . '</code>' . PHP_EOL;
                }

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $back,
                        ]
                    ),
                ];

                break;
            case 'subscribe_log':
                // 订阅记录
                if (Config::obtain('subscribe_log')) {
                    $logs = (new SubscribeLog())->orderBy('id', 'desc')->where('user_id', $this->user->id)->take(10)->get();
                    $text = '<strong>' . I18n::trans('bot.recent_sub_title', $locale) . '</strong>' . PHP_EOL . PHP_EOL;

                    foreach ($logs as $log) {
                        $text .= '<code>' . I18n::trans('bot.sub_log_item', $locale, [
                            'time' => Tools::toDateTime($log->request_time),
                            'ip' => $log->request_ip,
                            'location' => Tools::getIpLocation($log->request_ip),
                            'type' => $log->type,
                        ]) . '</code>' . PHP_EOL;
                    }

                    $text .= PHP_EOL . '<strong>' . I18n::trans('bot.geoip_notice', $locale) . '</strong>' . PHP_EOL;
                } else {
                    $text = I18n::trans('bot.sub_log_disabled', $locale);
                }

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $back,
                        ]
                    ),
                ];

                break;
            default:
                $temp = $this->getUserCenterKeyboard();

                $sendMessage = [
                    'text' => $temp['text'],
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ];

                break;
        }

        $this->replyWithMessage($sendMessage);
    }

    public function getUserEditKeyboard(): array
    {
        $locale = Message::getLocale($this->user);
        $text = I18n::trans('bot.edit_conn_title', $locale) . PHP_EOL . PHP_EOL;
        $text .= I18n::trans('bot.edit_conn_port', $locale, ['port' => $this->user->port]) . PHP_EOL;
        $text .= I18n::trans('bot.edit_conn_passwd', $locale, ['passwd' => $this->user->passwd]) . PHP_EOL;
        $text .= I18n::trans('bot.edit_conn_method', $locale, ['method' => $this->user->method]);

        $keyboard = [
            [
                [
                    'text' => I18n::trans('bot.btn_reset_sub', $locale),
                    'callback_data' => 'user.edit.update_link',
                ],
                [
                    'text' => I18n::trans('bot.btn_reset_passwd', $locale),
                    'callback_data' => 'user.edit.update_passwd',
                ],
            ],
            [
                [
                    'text' => I18n::trans('bot.btn_change_method', $locale),
                    'callback_data' => 'user.edit.encrypt',
                ],
                [
                    'text' => I18n::trans('bot.btn_unbind', $locale),
                    'callback_data' => 'user.edit.unbind',
                ],
            ],
            [
                [
                    'text' => I18n::trans('bot.btn_unban', $locale),
                    'callback_data' => 'user.edit.unban',
                ],
            ],
            [
                [
                    'text' => I18n::trans('bot.btn_main_menu', $locale),
                    'callback_data' => 'user.index',
                ],
            ],
        ];

        return [
            'text' => $text,
            'keyboard' => $keyboard,
        ];
    }

    /**
     * 用户编辑
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userEdit(): void
    {
        $locale = Message::getLocale($this->user);
        if ($this->chat_id < 0) {
            $this->answerCallbackQuery([
                'text' => I18n::trans('bot.group_action_denied', $locale),
                'show_alert' => true,
            ]);
        }

        $back = [
            [
                [
                    'text' => I18n::trans('bot.btn_main_menu', $locale),
                    'callback_data' => 'user.index',
                ],
                [
                    'text' => I18n::trans('bot.btn_prev_page', $locale),
                    'callback_data' => 'user.edit',
                ],
            ],
        ];

        $sendMessage = [];
        $CallbackDataExplode = explode('|', $this->callback_data);
        $Operate = explode('.', $CallbackDataExplode[0]);
        $OpEnd = end($Operate);

        switch ($OpEnd) {
            case 'update_link':
                // 重置订阅链接
                $this->user->removeLink();

                $this->answerCallbackQuery([
                    'text' => I18n::trans('bot.sub_reset_success', $locale),
                    'show_alert' => true,
                ]);

                $temp = $this->getUserSubscribeKeyboard();

                $sendMessage = [
                    'text' => $temp['text'],
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ];

                break;
            case 'update_passwd':
                // 重置链接密码
                $this->user->passwd = Tools::genPasswd(16);

                if ($this->user->save()) {
                    $answerCallbackQuery = I18n::trans('bot.passwd_reset_success', $locale);
                    $temp = $this->getUserSubscribeKeyboard();
                } else {
                    $answerCallbackQuery = I18n::trans('bot.passwd_reset_failed', $locale);
                    $temp = $this->getUserEditKeyboard();
                }

                $this->answerCallbackQuery([
                    'text' => $answerCallbackQuery,
                    'show_alert' => true,
                ]);

                $sendMessage = [
                    'text' => $temp['text'],
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ];

                break;
            case 'encrypt':
                // 加密方式更改
                $keyboard = $back;
                $method = Tools::getSsMethod();

                if (isset($CallbackDataExplode[1])) {
                    if (in_array($CallbackDataExplode[1], $method)) {
                        $temp = $this->user->setMethod($CallbackDataExplode[1]);
                        if ($temp['ok']) {
                            $text = I18n::trans('bot.current_method', $locale, ['method' => $this->user->method]) . PHP_EOL . PHP_EOL . $temp['msg'];
                        } else {
                            $text = I18n::trans('bot.error_retry', $locale) . PHP_EOL . PHP_EOL . $temp['msg'];
                        }
                    } else {
                        $text = I18n::trans('bot.error_retry', $locale);
                    }
                } else {
                    $Encrypts = [];

                    foreach ($method as $value) {
                        $Encrypts[] = [
                            'text' => $value,
                            'callback_data' => 'user.edit.encrypt|' . $value,
                        ];
                    }

                    $Encrypts = array_chunk($Encrypts, 2);
                    $keyboard = [];

                    foreach ($Encrypts as $Encrypt) {
                        $keyboard[] = $Encrypt;
                    }

                    $keyboard[] = $back[0];
                    $text = I18n::trans('bot.current_method', $locale, ['method' => $this->user->method]);
                }

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $keyboard,
                        ]
                    ),
                ];
                break;
            case 'unbind':
                // Telegram 账户解绑
                $this->allow_edit_message = false;
                $text = I18n::trans('bot.unbind_prompt_md', $locale);
                if (Config::obtain('telegram_unbind_kick_member')) {
                    $text .= PHP_EOL . PHP_EOL . I18n::trans('bot.unbind_kick_warning', $locale);
                }
                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'parse_mode' => 'Markdown',
                    'reply_markup' => null,
                ];
                break;
            case 'unban':
                // 群组解封
                $sendMessage = [
                    'text' => I18n::trans('bot.unban_warning', $locale),
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => I18n::trans('bot.btn_submit_unban', $locale),
                                        'callback_data' => 'user.edit.unban_update',
                                    ],
                                ],
                                $back[0],
                            ],
                        ]
                    ),
                ];

                break;
            case 'unban_update':
                // 提交群组解封
                $this->bot->unbanChatMember(
                    [
                        'chat_id' => Config::obtain('telegram_chatid'),
                        'user_id' => $this->trigger_user['id'],
                    ]
                );

                $this->answerCallbackQuery([
                    'text' => I18n::trans('bot.unban_submitted', $locale),
                    'show_alert' => true,
                ]);

                break;
            default:
                $temp = $this->getUserEditKeyboard();

                $sendMessage = [
                    'text' => $temp['text'],
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ];

                break;
        }

        if (! isset($sendMessage['parse_mode'])) {
            $sendMessage['parse_mode'] = 'HTML';
        }

        $this->replyWithMessage($sendMessage);
    }

    public function getUserSubscribeKeyboard(): array
    {
        $locale = Message::getLocale($this->user);
        $text = I18n::trans('bot.select_sub_type', $locale);

        $buttons = [
            [
                'text' => 'Clash',
                'callback_data' => 'user.subscribe|clash',
                'enable' => true,
            ],
            [
                'text' => 'SingBox',
                'callback_data' => 'user.subscribe|singbox',
                'enable' => true,
            ],
            [
                'text' => 'Json',
                'callback_data' => 'user.subscribe|json',
                'enable' => true,
            ],
            [
                'text' => 'Shadowsocks',
                'callback_data' => 'user.subscribe|ss',
                'enable' => (bool) Config::obtain('enable_ss_sub'),
            ],
            [
                'text' => 'SIP002',
                'callback_data' => 'user.subscribe|sip002',
                'enable' => (bool) Config::obtain('enable_ss_sub'),
            ],
            [
                'text' => 'SIP008',
                'callback_data' => 'user.subscribe|sip008',
                'enable' => (bool) Config::obtain('enable_ss_sub'),
            ],
            [
                'text' => 'V2Ray',
                'callback_data' => 'user.subscribe|v2',
                'enable' => (bool) Config::obtain('enable_v2_sub'),
            ],
            [
                'text' => 'V2RayJson',
                'callback_data' => 'user.subscribe|v2rayjson',
                'enable' => (bool) Config::obtain('enable_v2_sub'),
            ],
            [
                'text' => 'Trojan',
                'callback_data' => 'user.subscribe|trojan',
                'enable' => (bool) Config::obtain('enable_trojan_sub'),
            ],
        ];

        $validButtons = [];
        foreach ($buttons as $btn) {
            if ($btn['enable']) {
                $validButtons[] = [
                    'text' => $btn['text'],
                    'callback_data' => $btn['callback_data'],
                ];
            }
        }

        $keyboard = array_chunk($validButtons, 3);
        $keyboard[] = [
            [
                'text' => I18n::trans('bot.btn_main_menu', $locale),
                'callback_data' => 'user.index',
            ],
        ];

        return [
            'text' => $text,
            'keyboard' => $keyboard,
        ];
    }

    /**
     * 用户订阅
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userSubscribe(): void
    {
        $locale = Message::getLocale($this->user);
        $CallbackDataExplode = explode('|', $this->callback_data);
        // 订阅中心
        if (isset($CallbackDataExplode[1])) {
            $temp = [];

            $temp['keyboard'] = [
                [
                    [
                        'text' => I18n::trans('bot.btn_main_menu', $locale),
                        'callback_data' => 'user.index',
                    ],
                    [
                        'text' => I18n::trans('bot.btn_prev_page', $locale),
                        'callback_data' => 'user.subscribe',
                    ],
                ],
            ];

            $UniversalSub_Url = Subscribe::getUniversalSubLink($this->user);

            $text = match ($CallbackDataExplode[1]) {
                'json' => I18n::trans('bot.sub_url_universal', $locale, ['name' => 'Json']) . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/json</code>' . PHP_EOL . PHP_EOL,
                'clash' => I18n::trans('bot.sub_url_universal', $locale, ['name' => 'Clash']) . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/clash</code>' . PHP_EOL . PHP_EOL,
                'singbox' => I18n::trans('bot.sub_url_universal', $locale, ['name' => 'SingBox']) . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/singbox</code>' . PHP_EOL . PHP_EOL,
                'v2rayjson' => I18n::trans('bot.sub_url_universal', $locale, ['name' => 'V2RayJson']) . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/v2rayjson</code>' . PHP_EOL . PHP_EOL,
                'sip008' => I18n::trans('bot.sub_url_universal', $locale, ['name' => 'SIP008']) . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/sip008</code>' . PHP_EOL . PHP_EOL,
                'ss' => I18n::trans('bot.sub_url_client', $locale, ['name' => 'Shadowsocks']) . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/ss</code>' . PHP_EOL . PHP_EOL,
                'sip002' => I18n::trans('bot.sub_url_client', $locale, ['name' => 'SIP002']) . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/sip002</code>' . PHP_EOL . PHP_EOL,
                'v2' => I18n::trans('bot.sub_url_client', $locale, ['name' => 'V2Ray']) . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/v2ray</code>' . PHP_EOL . PHP_EOL,
                'trojan' => I18n::trans('bot.sub_url_client', $locale, ['name' => 'Trojan']) . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/trojan</code>' . PHP_EOL . PHP_EOL,
                default => I18n::trans('bot.unknown_param', $locale) . PHP_EOL . PHP_EOL,
            };

            $sendMessage = [
                'text' => $text,
                'disable_web_page_preview' => true,
                'reply_to_message_id' => null,
                'reply_markup' => json_encode(
                    [
                        'inline_keyboard' => $temp['keyboard'],
                    ]
                ),
            ];
        } else {
            $temp = $this->getUserSubscribeKeyboard();

            $sendMessage = [
                'text' => $temp['text'],
                'disable_web_page_preview' => false,
                'reply_to_message_id' => null,
                'reply_markup' => json_encode(
                    [
                        'inline_keyboard' => $temp['keyboard'],
                    ]
                ),
            ];
        }

        $this->replyWithMessage(
            array_merge(
                [
                    'parse_mode' => 'HTML',
                ],
                $sendMessage
            )
        );
    }

    public function getUserInviteKeyboard(): array
    {
        $locale = Message::getLocale($this->user);
        $paybacks_sum = (new Payback())->where('ref_by', $this->user->id)->sum('ref_get');

        if (is_null($paybacks_sum)) {
            $paybacks_sum = 0;
        }

        $text = [
            '<strong>' . I18n::trans('bot.invite_per_user_title', $locale) . '</strong>',
            '',
            I18n::trans('bot.invite_traffic_reward', $locale, ['traffic' => Config::obtain('invite_reg_traffic_reward')]),
            I18n::trans('bot.invite_money_reward', $locale, ['money' => Config::obtain('invite_reg_money_reward')]),
            I18n::trans('bot.invite_rebate_rate', $locale, ['rate' => Config::obtain('invite_reward_rate') * 100]),
            '',
            I18n::trans('bot.invite_total_rebate', $locale, ['amount' => $paybacks_sum]),
        ];

        $keyboard = [
            [
                [
                    'text' => I18n::trans('bot.btn_get_invite_link', $locale),
                    'callback_data' => 'user.invite.get',
                ],
            ],
            [
                [
                    'text' => I18n::trans('bot.btn_main_menu', $locale),
                    'callback_data' => 'user.index',
                ],
            ],
        ];

        return [
            'text' => implode(PHP_EOL, $text),
            'keyboard' => $keyboard,
        ];
    }

    /**
     * 分享计划
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userInvite(): void
    {
        $CallbackDataExplode = explode('|', $this->callback_data);
        $Operate = explode('.', $CallbackDataExplode[0]);
        $OpEnd = end($Operate);

        if ($OpEnd === 'get') {
            $this->allow_edit_message = false;
            $code = (new InviteCode())->where('user_id', $this->user->id)->first();

            if ($code === null) {
                $code = (new InviteCode())->add($this->user->id);
            }

            $inviteUrl = $_ENV['baseUrl'] . '/auth/register?code=' . $code->code;
            $text = '<a href="' . $inviteUrl . '">' . $inviteUrl . '</a>';

            $sendMessage = [
                'text' => $text,
                'disable_web_page_preview' => false,
                'reply_to_message_id' => null,
                'reply_markup' => null,
            ];
        } else {
            $temp = $this->getUserInviteKeyboard();

            $sendMessage = [
                'text' => $temp['text'],
                'disable_web_page_preview' => false,
                'reply_to_message_id' => null,
                'reply_markup' => json_encode(
                    [
                        'inline_keyboard' => $temp['keyboard'],
                    ]
                ),
            ];
        }

        $this->replyWithMessage(
            array_merge(
                [
                    'parse_mode' => 'HTML',
                ],
                $sendMessage
            )
        );
    }

    /**
     * 每日签到
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userCheckin(): void
    {
        $locale = Message::getLocale($this->user);
        if ($this->user->isAbleToCheckin()) {
            $traffic = Reward::issueCheckinReward($this->user->id);

            if (! $traffic) {
                $msg = I18n::trans('bot.checkin_failed', $locale);
            } else {
                $msg = I18n::trans('bot.checkin_success', $locale, ['traffic' => $traffic]);
            }
        } else {
            $msg = I18n::trans('bot.checkin_already', $locale);
        }

        $this->answerCallbackQuery([
            'text' => $msg,
            'show_alert' => true,
        ]);
        // 回送信息
        if ($this->chat_id > 0) {
            $temp = self::getUserIndexKeyboard($this->user);
        } else {
            $temp['text'] = Message::getUserTrafficInfo($this->user);

            $temp['keyboard'] = [
                [
                    [
                        'text' => (! $this->user->isAbleToCheckin()
                            ? I18n::trans('bot.btn_checked_in', $locale)
                            : I18n::trans('bot.btn_checkin', $locale)),
                        'callback_data' => 'user.checkin.' . $this->trigger_user['id'],
                    ],
                ],
            ];
        }

        $this->replyWithMessage([
            'text' => $temp['text'] . PHP_EOL . PHP_EOL . $msg,
            'reply_to_message_id' => $this->message_id,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(
                [
                    'inline_keyboard' => $temp['keyboard'],
                ]
            ),
        ]);
    }
}
