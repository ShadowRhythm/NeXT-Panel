<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\LoginIp;
use App\Models\Node;
use App\Models\OnlineLog;
use App\Models\SubscribeLog;
use App\Models\UserDeviceAlias;
use App\Models\UserSession;
use App\Services\Auth;
use App\Services\Auth\Cookie as AuthCookie;
use App\Services\ClientDetector;
use App\Services\GlobalIpLimit;
use App\Services\I18n;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception;
use Throwable;
use function hash_equals;
use function mb_substr;
use function min;
use function round;
use function str_replace;
use function time;
use function trim;

final class ProfileController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $now = time();
        $logins = [];
        $subs = [];

        $ips = (new OnlineLog())->where('user_id', $this->user->id)
            ->where('last_time', '>', $now - GlobalIpLimit::ACTIVE_WINDOW_SECONDS)
            ->where('kicked_at', 0)
            ->orderByDesc('last_time')
            ->get();

        if (Config::obtain('login_log')) {
            $logins = (new LoginIp())->where('userid', $this->user->id)
                ->where('type', '=', 0)
                ->orderBy('datetime', 'desc')
                ->take(10)
                ->get();
        }

        $recentSubsAll = [];
        if (Config::obtain('subscribe_log')) {
            $recentSubsAll = (new SubscribeLog())->where('user_id', $this->user->id)
                ->orderBy('id', 'desc')
                ->take(50)
                ->get();
            $subs = $recentSubsAll->take(10);
        }

        // Map IP -> most recent proxy client from subscribe_log & aliases
        $subClientByIp = [];
        $subClients = [];
        foreach ($recentSubsAll as $sub) {
            $parsed = ClientDetector::parseProxyClient((string) $sub->request_user_agent, (string) $sub->type);
            $subIp = str_replace('::ffff:', '', (string) $sub->request_ip);

            if ($subIp !== '' && ! isset($subClientByIp[$subIp])) {
                $subClientByIp[$subIp] = $parsed;
            }

            $dedupKey = $parsed['client'] . '|' . $parsed['os'] . '|' . (string) $sub->type;
            if (! isset($subClients[$dedupKey])) {
                $subClients[$dedupKey] = (object) [
                    'client' => $parsed['client'],
                    'version' => $parsed['version'],
                    'os' => $parsed['os'],
                    'icon' => $parsed['icon'],
                    'type' => (string) $sub->type,
                    'ip' => $subIp,
                    'location' => Tools::getIpLocation($subIp),
                    'last_time' => Tools::toDateTime((int) $sub->request_time),
                ];
            }
        }

        $aliases = [];
        try {
            $aliases = UserDeviceAlias::where('user_id', $this->user->id)
                ->pluck('alias', 'ip')
                ->toArray();
        } catch (Throwable) {
            $aliases = [];
        }

        $nodeIds = $ips->pluck('node_id')->unique()->filter();
        $nodes = ! $nodeIds->isEmpty() ? Node::whereIn('id', $nodeIds)->pluck('name', 'id') : collect();

        foreach ($ips as $ip) {
            $displayIp = str_replace('::ffff:', '', (string) $ip->ip);
            $ip->ip = $displayIp;
            $ip->location = Tools::getIpLocation($displayIp);
            $ip->node_name = $nodes[$ip->node_id] ?? I18n::trans('user.unknown_node', $locale);
            $ip->last_time = Tools::toDateTime((int) $ip->last_time);
            $ip->alias = (string) ($aliases[$displayIp] ?? '');
            $matchedClient = $subClientByIp[$displayIp] ?? null;
            $ip->matched_client = $matchedClient !== null
                ? ($matchedClient['client'] . ($matchedClient['os'] !== 'Unknown' ? ' (' . $matchedClient['os'] . ')' : ''))
                : '';
            $ip->client_icon = $matchedClient['icon'] ?? 'ti-devices';
        }

        foreach ($logins as $login) {
            $login->datetime = Tools::toDateTime((int) $login->datetime);
            $login->location = Tools::getIpLocation($login->ip);
        }

        foreach ($subs as $sub) {
            $sub->request_time = Tools::toDateTime($sub->request_time);
        }

        $webSessions = [];
        $currentHash = AuthCookie::getCurrentTokenHash();
        try {
            $sessionRows = UserSession::where('user_id', $this->user->id)
                ->where('expires_at', '>', $now)
                ->orderByDesc('last_active_at')
                ->get();

            foreach ($sessionRows as $session) {
                $isCurrent = $currentHash !== '' && hash_equals($currentHash, (string) $session->token_hash);
                $webSessions[] = (object) [
                    'id' => (int) $session->id,
                    'device_name' => (string) ($session->device_name !== '' ? $session->device_name : 'Browser'),
                    'os' => (string) $session->os,
                    'browser' => (string) $session->browser,
                    'icon' => ClientDetector::parseWebAgent((string) $session->user_agent)['icon'],
                    'ip' => (string) $session->ip,
                    'location' => Tools::getIpLocation((string) $session->ip),
                    'last_active' => Tools::toDateTime((int) $session->last_active_at),
                    'expires_at' => Tools::toDateTime((int) $session->expires_at),
                    'is_current' => $isCurrent,
                ];
            }
        } catch (Throwable) {
            $webSessions = [];
        }

        $activeIpCount = $ips->count();
        $ipLimit = (int) $this->user->node_iplimit;
        $ipQuotaPercent = $ipLimit > 0 ? min(100, (int) round(($activeIpCount / $ipLimit) * 100)) : 0;
        $isOverIpLimit = $ipLimit > 0 && $activeIpCount > $ipLimit;

        return $response->write(
            $this->view()
                ->assign('ips', $ips)
                ->assign('logins', $logins)
                ->assign('subs', $subs)
                ->assign('sub_clients', array_values($subClients))
                ->assign('web_sessions', $webSessions)
                ->assign('active_ip_count', $activeIpCount)
                ->assign('ip_limit', $ipLimit)
                ->assign('ip_quota_percent', $ipQuotaPercent)
                ->assign('is_over_ip_limit', $isOverIpLimit)
                ->fetch('user/profile.tpl')
        );
    }

    public function saveDeviceAlias(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $ip = trim((string) $this->antiXss->xss_clean((string) $request->getParam('ip')));
        $alias = mb_substr(trim((string) $this->antiXss->xss_clean((string) $request->getParam('alias'))), 0, 64);

        if ($ip === '' || (! Tools::isIPv4($ip) && ! Tools::isIPv6($ip))) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_parameter', $locale));
        }

        if ($alias === '') {
            UserDeviceAlias::where('user_id', $this->user->id)->where('ip', $ip)->delete();
        } else {
            $record = UserDeviceAlias::where('user_id', $this->user->id)->where('ip', $ip)->first();
            if ($record === null) {
                $record = new UserDeviceAlias();
                $record->user_id = (int) $this->user->id;
                $record->ip = $ip;
            }
            $record->alias = $alias;
            $record->updated_at = time();
            $record->save();
        }

        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.update_success', $locale),
        ]);
    }

    public function removeOnlineDevice(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $id = (int) ($args['id'] ?? 0);

        $log = OnlineLog::where('user_id', $this->user->id)
            ->where('id', $id)
            ->where('kicked_at', 0)
            ->first();
        if ($log === null) {
            return ResponseHelper::error($response, I18n::trans('user.device_not_found', $locale));
        }

        $nodeId = (int) $log->node_id;
        $userId = (int) $log->user_id;
        $humanIp = $log->ip();
        $now = time();

        $updated = OnlineLog::where('user_id', $this->user->id)
            ->where('id', $id)
            ->where('kicked_at', 0)
            ->update(['kicked_at' => $now]);
        if ($updated === 0) {
            return ResponseHelper::error($response, I18n::trans('user.device_not_found', $locale));
        }

        GlobalIpLimit::kickNodeIp($nodeId, $userId, $humanIp);

        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.device_delete_success', $locale),
        ]);
    }

    public function kickAllProxyDevices(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $user = $this->user;

        $user->passwd = Tools::genPasswd(16);
        $user->uuid = (string) Uuid::uuid4();
        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.reset_failed', $locale));
        }

        $user->removeLink();

        $now = time();
        $activeLogs = OnlineLog::where('user_id', $user->id)
            ->where('last_time', '>', $now - GlobalIpLimit::ACTIVE_WINDOW_SECONDS)
            ->where('kicked_at', 0)
            ->get(['node_id', 'user_id', 'ip']);
        OnlineLog::where('user_id', $user->id)
            ->where('kicked_at', 0)
            ->update(['kicked_at' => $now]);

        foreach ($activeLogs as $log) {
            GlobalIpLimit::kickNodeIp((int) $log->node_id, (int) $log->user_id, $log->ip());
        }

        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.kick_all_proxy_success', $locale),
        ]);
    }

    public function revokeSession(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $sessionId = (int) ($args['id'] ?? 0);

        $result = AuthCookie::revokeSessionById((int) $this->user->id, $sessionId);
        if (! $result['revoked']) {
            return ResponseHelper::error($response, I18n::trans('user.device_not_found', $locale));
        }

        if ($result['is_current']) {
            Auth::logout();

            return $response->withHeader('HX-Redirect', '/auth/login')->withJson([
                'ret' => 1,
                'msg' => I18n::trans('user.session_revoked', $locale),
            ]);
        }

        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.session_revoked', $locale),
        ]);
    }

    public function kickOtherSessions(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        AuthCookie::revokeOtherSessions((int) $this->user->id);

        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.other_sessions_revoked', $locale),
        ]);
    }
}
