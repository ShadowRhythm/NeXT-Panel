<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Ticket;
use App\Models\User;
use App\Services\I18n;
use App\Services\LLM;
use App\Services\Notification;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function array_merge;
use function count;
use function is_array;
use function json_decode;
use function json_encode;
use function nl2br;
use function time;

final class TicketController extends BaseController
{
    private static array $details =
        [
            'field' => [
                'op' => '操作',
                'id' => '工单ID',
                'title' => '主题',
                'status' => '工单状态',
                'type' => '工单类型',
                'userid' => '提交用户',
                'datetime' => '创建时间',
            ],
        ];

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/ticket/index.tpl')
        );
    }

    public function reply(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $comment = $request->getParam('comment') ?? '';

        if ($comment === '') {
            return ResponseHelper::error($response, '评论不能为空');
        }

        $ticket = (new Ticket())->where('id', $id)->first();

        if ($ticket === null) {
            return ResponseHelper::error($response, '工单不存在');
        }

        $content_old = json_decode((string) $ticket->content, true);
        if (! is_array($content_old)) {
            $content_old = [];
        }
        $lastCommentId = $content_old !== [] ? (int) ($content_old[count($content_old) - 1]['comment_id'] ?? 0) : -1;
        $content_new = [
            [
                'comment_id' => $lastCommentId + 1,
                'commenter_type' => 'admin',
                'commenter_name' => 'Admin',
                'comment' => $this->antiXss->xss_clean($comment),
                'datetime' => time(),
            ],
        ];

        $ticket->content = json_encode(array_merge($content_old, $content_new));
        $ticket->status = 'open_wait_user';
        $ticket->save();

        $user = (new User())->find($ticket->userid);
        $userLocale = $user !== null ? I18n::getCurrentLocale($user) : 'en_US';

        try {
            if ($user !== null) {
                Notification::notifyUser(
                    $user,
                    I18n::trans('email.ticket_replied_subject', $userLocale, ['app_name' => $_ENV['appName']]),
                    I18n::trans('email.ticket_replied_user_body', $userLocale, [
                        'ticket_url' => $_ENV['baseUrl'] . '/user/ticket/' . $ticket->id . '/view',
                        'ticket_id' => (string) $ticket->id,
                    ])
                );
            }
        } catch (TelegramSDKException|GuzzleException|ClientExceptionInterface $e) {
            return $response->withHeader('HX-Refresh', 'true');
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function llmReply(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $ticket = (new Ticket())->where('id', $id)->first();

        if ($ticket === null) {
            return ResponseHelper::error($response, '工单不存在');
        }

        $content_old = json_decode((string) $ticket->content, true);
        if (! is_array($content_old)) {
            $content_old = [];
        }

        if (count($content_old) === 1) {
            $context = [
                [
                    'role' => 'user',
                    'content' => $ticket->title,
                ],
                [
                    'role' => 'user',
                    'content' => $content_old[0]['comment'] ?? '',
                ],
            ];
        } else {
            $context = [
                [
                    'role' => 'user',
                    'content' => $ticket->title,
                ],
            ];

            foreach ($content_old as $comment) {
                $type = $comment['commenter_type'] ?? null;
                $role = match ($type) {
                    'admin', 'llm' => 'admin',
                    'user' => 'user',
                    default => ($comment['commenter_name'] ?? '') === 'Admin' ? 'admin' : 'user',
                };
                $context[] = [
                    'role' => $role,
                    'content' => $comment['comment'] ?? '',
                ];
            }
        }

        $llm_response = LLM::genTextResponseWithContext($context);

        $lastCommentId = $content_old !== [] ? (int) ($content_old[count($content_old) - 1]['comment_id'] ?? 0) : -1;
        $content_new = [
            [
                'comment_id' => $lastCommentId + 1,
                'commenter_type' => 'llm',
                'commenter_name' => 'AI Assistant',
                'comment' => $this->antiXss->xss_clean($llm_response),
                'datetime' => time(),
            ],
        ];

        $ticket->content = json_encode(array_merge($content_old, $content_new));
        $ticket->status = 'open_wait_user';
        $ticket->save();

        $user = (new User())->find($ticket->userid);
        $userLocale = $user !== null ? I18n::getCurrentLocale($user) : 'en_US';

        try {
            if ($user !== null) {
                Notification::notifyUser(
                    $user,
                    I18n::trans('email.ticket_replied_subject', $userLocale, ['app_name' => $_ENV['appName']]),
                    I18n::trans('email.ticket_ai_replied_user_body', $userLocale, [
                        'ticket_url' => $_ENV['baseUrl'] . '/user/ticket/' . $ticket->id . '/view',
                        'ticket_id' => (string) $ticket->id,
                    ])
                );
            }
        } catch (TelegramSDKException|GuzzleException|ClientExceptionInterface $e) {
            return $response->withHeader('HX-Refresh', 'true');
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    /**
     * 后台查看指定工单
     *
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $ticket = (new Ticket())->where('id', '=', $id)->first();

        if ($ticket === null) {
            return $response->withRedirect('/admin/ticket');
        }

        $comments = json_decode($ticket->content);

        foreach ($comments as $comment) {
            $comment->comment = nl2br($comment->comment);
            $comment->datetime = Tools::toDateTime((int) $comment->datetime);
        }

        return $response->write(
            $this->view()
                ->assign('ticket', $ticket)
                ->assign('comments', $comments)
                ->fetch('admin/ticket/view.tpl')
        );
    }

    /**
     * 后台关闭工单
     */
    public function close(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $ticket = (new Ticket())->where('id', '=', $id)->first();

        if ($ticket === null) {
            return ResponseHelper::error($response, '工单不存在');
        }

        if ($ticket->status === 'closed') {
            return ResponseHelper::error($response, '工单已关闭，无需重复操作');
        }

        $ticket->status = 'closed';
        $ticket->save();

        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => '工单关闭成功',
        ]);
    }

    /**
     * 后台删除工单
     */
    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        (new Ticket())->where('id', '=', $id)->delete();

        return ResponseHelper::success($response, '工单删除成功');
    }

    /**
     * 后台工单页面 Ajax
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $tickets = (new Ticket())->orderBy('id', 'desc')->get();

        foreach ($tickets as $ticket) {
            $ticket->op = '<button class="btn btn-red" id="delete-ticket" 
            onclick="deleteTicket(' . $ticket->id . ')">删除</button>';

            if ($ticket->status !== 'closed') {
                $ticket->op .= '
                <button class="btn btn-orange" id="close-ticket" 
                onclick="closeTicket(' . $ticket->id . ')">关闭</button>';
            }

            $ticket->op .= '
            <a class="btn btn-primary" href="/admin/ticket/' . $ticket->id . '/view">查看</a>';
            $ticket->status = $ticket->status();
            $ticket->type = $ticket->type();
            $ticket->datetime = Tools::toDateTime((int) $ticket->datetime);
        }

        return $response->withJson([
            'tickets' => $tickets,
        ]);
    }
}
