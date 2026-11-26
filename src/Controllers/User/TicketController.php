<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\Ticket;
use App\Services\I18n;
use App\Services\Notification;
use App\Services\RateLimit;
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
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if (! Config::obtain('enable_ticket')) {
            return $response->withRedirect('/user');
        }

        $tickets = (new Ticket())->where('userid', $this->user->id)->orderBy('datetime', 'desc')->get();

        foreach ($tickets as $ticket) {
            $ticket->status = $ticket->status();
            $ticket->type = $ticket->type();
            $ticket->datetime = Tools::toDateTime((int) $ticket->datetime);
        }

        return $response->write(
            $this->view()
                ->assign('tickets', $tickets)
                ->fetch('user/ticket/index.tpl')
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws TelegramSDKException
     * @throws GuzzleException
     */
    public function add(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $title = $request->getParam('title') ?? '';
        $comment = $request->getParam('comment') ?? '';
        $type = $request->getParam('type') ?? '';

        if (! Config::obtain('enable_ticket') ||
            $this->user->is_shadow_banned ||
            ! (new RateLimit())->checkRateLimit('ticket', (string) $this->user->id) ||
            $title === '' ||
            $comment === '' ||
            $type === ''
        ) {
            return ResponseHelper::error($response, I18n::trans('user.ticket_create_failed', $locale));
        }

        $content = [
            [
                'comment_id' => 0,
                'commenter_type' => 'user',
                'commenter_name' => $this->user->user_name,
                'comment' => $this->antiXss->xss_clean($comment),
                'datetime' => time(),
            ],
        ];

        $ticket = new Ticket();
        $ticket->title = $this->antiXss->xss_clean($title);
        $ticket->content = json_encode($content);
        $ticket->userid = $this->user->id;
        $ticket->datetime = time();
        $ticket->status = 'open_wait_admin';
        $ticket->type = $this->antiXss->xss_clean($type);
        $ticket->save();

        if (Config::obtain('mail_ticket')) {
            Notification::notifyAdminTrans(
                'email.new_ticket_subject',
                'email.new_ticket_body',
                ['app_name' => $_ENV['appName']]
            );
        }

        return $response->withHeader('HX-Redirect', '/user/ticket/' . $ticket->id . '/view');
    }

    /**
     * @throws GuzzleException
     * @throws TelegramSDKException
     * @throws ClientExceptionInterface
     */
    public function reply(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $id = $args['id'];
        $comment = $request->getParam('comment') ?? '';

        if (! Config::obtain('enable_ticket') ||
            $this->user->is_shadow_banned ||
            $comment === ''
        ) {
            return ResponseHelper::error($response, I18n::trans('user.ticket_reply_failed', $locale));
        }

        $ticket = (new Ticket())->where('id', $id)->where('userid', $this->user->id)->first();

        if ($ticket === null) {
            return ResponseHelper::error($response, I18n::trans('user.ticket_not_found', $locale));
        }

        $content_old = json_decode((string) $ticket->content, true);
        if (! is_array($content_old)) {
            $content_old = [];
        }
        $lastCommentId = $content_old !== [] ? (int) ($content_old[count($content_old) - 1]['comment_id'] ?? 0) : -1;
        $content_new = [
            [
                'comment_id' => $lastCommentId + 1,
                'commenter_type' => 'user',
                'commenter_name' => $this->user->user_name,
                'comment' => $this->antiXss->xss_clean($comment),
                'datetime' => time(),
            ],
        ];

        $ticket->content = json_encode(array_merge($content_old, $content_new));
        $ticket->status = 'open_wait_admin';
        $ticket->save();

        if (Config::obtain('mail_ticket')) {
            Notification::notifyAdminTrans(
                'email.ticket_replied_subject',
                'email.ticket_replied_admin_body',
                [
                    'app_name' => $_ENV['appName'],
                    'ticket_url' => $_ENV['baseUrl'] . '/admin/ticket/' . $ticket->id . '/view',
                    'ticket_id' => (string) $ticket->id,
                ]
            );
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function close(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $id = $args['id'];
        $ticket = (new Ticket())->where('id', '=', $id)->where('userid', $this->user->id)->first();

        if ($ticket === null) {
            return ResponseHelper::error($response, I18n::trans('user.ticket_not_found', $locale));
        }

        if ($ticket->status === 'closed') {
            return ResponseHelper::error($response, I18n::trans('user.ticket_already_closed', $locale));
        }

        $ticket->status = 'closed';
        $ticket->save();

        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.ticket_close_success', $locale),
        ]);
    }

    /**
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if (! Config::obtain('enable_ticket')) {
            return $response->withRedirect('/user');
        }

        $id = $args['id'];
        $ticket = (new Ticket())->where('id', '=', $id)->where('userid', $this->user->id)->first();

        if ($ticket === null) {
            return $response->withRedirect('/user/ticket');
        }

        $comments = json_decode($ticket->content);

        foreach ($comments as $comment) {
            $comment->comment = nl2br($comment->comment);
            $comment->datetime = Tools::toDateTime((int) $comment->datetime);
        }

        $ticket->status = $ticket->status();
        $ticket->type = $ticket->type();
        $ticket->datetime = Tools::toDateTime((int) $ticket->datetime);

        return $response->write(
            $this->view()
                ->assign('ticket', $ticket)
                ->assign('comments', $comments)
                ->fetch('user/ticket/view.tpl')
        );
    }
}
