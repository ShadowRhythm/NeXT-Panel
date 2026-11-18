<?php

declare(strict_types=1);

namespace App\Command;

use App\Models\Config;
use App\Services\DB;
use App\Services\Cron as CronService;
use App\Services\Detect;
use Exception;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;
use function date;
use function mktime;
use function time;

final class Cron extends Command
{
    public string $description = <<<EOL
├─=: php xcat Cron - 站点定时任务，每五分钟
EOL;

    /**
     * @throws TelegramSDKException
     * @throws Exception
     */
    public function boot(): void
    {
        ini_set('memory_limit', '-1');

        $lockName = 'sspanel-cron:' . substr(hash('sha256', $_ENV['db_database']), 0, 32);
        $statement = DB::getPdo()->prepare('SELECT GET_LOCK(?, 0)');
        $statement->execute([$lockName]);
        $lock = $statement->fetchColumn();
        if ((int) $lock !== 1) {
            echo 'Cron is already running; this invocation was skipped.' . PHP_EOL;
            return;
        }

        register_shutdown_function(static function () use ($lockName): void {
            $statement = DB::getPdo()->prepare('SELECT RELEASE_LOCK(?)');
            $statement->execute([$lockName]);
        });

        // Log current hour & minute
        $hour = (int) date('H');
        $minute = (int) date('i');

        $jobs = new CronService();

        // Run new shop related jobs
        $this->runJob('auto-renew-orders', static fn () => $jobs->processAutoRenew());
        $this->runJob('pending-orders', static fn () => $jobs->processPendingOrder());
        $this->runJob('activate-tabp-orders', static fn () => $jobs->processTabpOrderActivation());
        $this->runJob('activate-bandwidth-orders', static fn () => $jobs->processBandwidthOrderActivation());
        $this->runJob('activate-time-orders', static fn () => $jobs->processTimeOrderActivation());
        $this->runJob('activate-topup-orders', static fn () => $jobs->processTopupOrderActivation());

        // Run user related jobs
        $this->runJob('expire-paid-users', static fn () => $jobs->expirePaidUserAccount());
        $this->runJob('usage-limit-notifications', static fn () => $jobs->sendPaidUserUsageLimitNotification());

        // Run node related jobs
        $this->runJob('update-node-ip', static fn () => $jobs->updateNodeIp());

        if (Config::obtain('enable_detect_offline')) {
            $this->runJob('detect-node-offline', static fn () => $jobs->detectNodeOffline());
        }

        // Run daily job
        $targetDailyTime = mktime(
            Config::obtain('daily_job_hour'),
            Config::obtain('daily_job_minute'),
            0,
            (int) date('m'),
            (int) date('d'),
            (int) date('Y')
        );

        if (time() >= $targetDailyTime && Config::obtain('last_daily_job_time') < $targetDailyTime) {
            $dailySucceeded = true;
            $dailySucceeded = $this->runJob('clean-db', static fn () => $jobs->cleanDb()) && $dailySucceeded;
            $dailySucceeded = $this->runJob('reset-node-bandwidth', static fn () => $jobs->resetNodeBandwidth()) && $dailySucceeded;
            $dailySucceeded = $this->runJob('reset-free-user-bandwidth', static fn () => $jobs->resetFreeUserBandwidth()) && $dailySucceeded;
            $dailySucceeded = $this->runJob('daily-traffic-report', static fn () => $jobs->sendDailyTrafficReport()) && $dailySucceeded;

            if (Config::obtain('enable_detect_inactive_user')) {
                $dailySucceeded = $this->runJob('detect-inactive-users', static fn () => $jobs->detectInactiveUser()) && $dailySucceeded;
            }

            if (Config::obtain('remove_inactive_user_link_and_invite')) {
                $dailySucceeded = $this->runJob('remove-inactive-user-links', static fn () => $jobs->removeInactiveUserLinkAndInvite()) && $dailySucceeded;
            }

            if (Config::obtain('im_bot_group_notify_diary')) {
                $dailySucceeded = $this->runJob('daily-diary-notification', static fn () => $jobs->sendDiaryNotification()) && $dailySucceeded;
            }

            $dailySucceeded = $this->runJob('aggregate-daily-stat', static fn () => $jobs->aggregateDailyStat()) && $dailySucceeded;
            $dailySucceeded = $this->runJob('reset-today-bandwidth', static fn () => $jobs->resetTodayBandwidth()) && $dailySucceeded;

            if (Config::obtain('im_bot_group_notify_daily_job')) {
                $dailySucceeded = $this->runJob('daily-job-notification', static fn () => $jobs->sendDailyJobNotification()) && $dailySucceeded;
            }

            if ($dailySucceeded) {
                Config::set('last_daily_job_time', time());
            }
        }

        // Daily finance report
        if (Config::obtain('enable_daily_finance_mail')
            && $hour === 0
            && $minute === 0
        ) {
            $this->runJob('daily-finance-report', static fn () => $jobs->sendDailyFinanceMail());
        }

        // Weekly finance report
        if (Config::obtain('enable_weekly_finance_mail')
            && $hour === 0
            && $minute === 0
            && date('w') === '1'
        ) {
            $this->runJob('weekly-finance-report', static fn () => $jobs->sendWeeklyFinanceMail());
        }

        // Monthly finance report
        if (Config::obtain('enable_monthly_finance_mail')
            && $hour === 0
            && $minute === 0
            && date('d') === '01'
        ) {
            $this->runJob('monthly-finance-report', static fn () => $jobs->sendMonthlyFinanceMail());
        }

        // Detect GFW
        if (Config::obtain('enable_detect_gfw') && $minute === 0
        ) {
            $detect = new Detect();
            $this->runJob('detect-gfw', static fn () => $detect->gfw());
        }

        // Detect ban
        if (Config::obtain('enable_detect_ban') && $minute === 0
        ) {
            $detect = new Detect();
            $this->runJob('detect-ban', static fn () => $detect->ban());
        }

        // Run email queue
        $this->runJob('process-email-queue', static fn () => $jobs->processEmailQueue());
    }

    private function runJob(string $name, callable $job): bool
    {
        $started = microtime(true);
        try {
            $job();
            error_log(sprintf('Cron job %s completed in %.3fs', $name, microtime(true) - $started));
            return true;
        } catch (Throwable $e) {
            error_log(sprintf('Cron job %s failed after %.3fs: %s', $name, microtime(true) - $started, $e));
            return false;
        }
    }
}
