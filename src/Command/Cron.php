<?php

declare(strict_types=1);

namespace App\Command;

use App\Models\Config;
use App\Services\DB;
use App\Services\Cron as CronService;
use App\Services\Detect;
use App\Services\Notification;
use Exception;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;
use function array_keys;
use function array_map;
use function date;
use function error_log;
use function hash;
use function implode;
use function ini_set;
use function mb_substr;
use function microtime;
use function mktime;
use function register_shutdown_function;
use function sprintf;
use function substr;
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
            (int) Config::obtain('daily_job_hour'),
            (int) Config::obtain('daily_job_minute'),
            0,
            (int) date('m'),
            (int) date('d'),
            (int) date('Y')
        );

        $today = date('Y-m-d');
        $lastDailyTime = (int) Config::obtain('last_daily_job_time');
        $lastRunDate = $lastDailyTime > 0 ? date('Y-m-d', $lastDailyTime) : '';

        if ($today !== $lastRunDate && time() >= $targetDailyTime) {
            // 立即记录执行时间锁定当天，确保每天只触发一次，彻底杜绝非设置时间重复执行
            Config::set('last_daily_job_time', time());
            $this->failedDailyJobs = [];

            $this->runDailyJob('reset-node-bandwidth', static fn () => $jobs->resetNodeBandwidth());
            $this->runDailyJob('reset-free-user-bandwidth', static fn () => $jobs->resetFreeUserBandwidth());
            $this->runDailyJob('daily-traffic-report', static fn () => $jobs->sendDailyTrafficReport());

            if (Config::obtain('enable_detect_inactive_user')) {
                $this->runDailyJob('detect-inactive-users', static fn () => $jobs->detectInactiveUser());
            }

            if (Config::obtain('remove_inactive_user_link_and_invite')) {
                $this->runDailyJob('remove-inactive-user-links', static fn () => $jobs->removeInactiveUserLinkAndInvite());
            }

            if (Config::obtain('im_bot_group_notify_diary')) {
                $this->runDailyJob('daily-diary-notification', static fn () => $jobs->sendDiaryNotification());
            }

            $this->runDailyJob('aggregate-daily-stat', static fn () => $jobs->aggregateDailyStat());
            $this->runDailyJob('reset-today-bandwidth', static fn () => $jobs->resetTodayBandwidth());
            // clean-db 放在统计与重设之后，避免在低保留天数设置下提前删掉前一日日志影响聚合与简报
            $this->runDailyJob('clean-db', static fn () => $jobs->cleanDb());
            $this->runDailyJob('update-qqwry-ipdb', static fn () => $jobs->updateQQWryIpdb());

            if (empty($this->failedDailyJobs)) {
                Config::set('last_daily_job_status', 'success');
                if (Config::obtain('im_bot_group_notify_daily_job')) {
                    $this->runDailyJob('daily-job-notification', static fn () => $jobs->sendDailyJobNotification());
                }
            } else {
                $failedInfo = [];
                foreach ($this->failedDailyJobs as $name => $err) {
                    $failedInfo[] = "{$name}: {$err}";
                }
                $statusText = mb_substr(implode('; ', $failedInfo), 0, 1900);
                Config::set('last_daily_job_status', $statusText);

                // 发送错误通知给管理员
                try {
                    $lines = array_map(
                        static fn ($k, $v) => "• {$k}: {$v}",
                        array_keys($this->failedDailyJobs),
                        $this->failedDailyJobs
                    );
                    $adminMsg = "系统每日任务部分子任务执行失败：\n" . implode("\n", $lines);
                    Notification::notifyAdmin($_ENV['appName'] . '-每日任务执行异常', $adminMsg);
                } catch (Throwable $e) {
                    error_log('Failed to notify admin of daily job error: ' . $e->getMessage());
                }
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

    private array $failedDailyJobs = [];

    private function runDailyJob(string $name, callable $job): bool
    {
        $started = microtime(true);
        try {
            $job();
            error_log(sprintf('Daily cron job %s completed in %.3fs', $name, microtime(true) - $started));
            return true;
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
            error_log(sprintf('Daily cron job %s failed after %.3fs: %s', $name, microtime(true) - $started, $e));
            $this->failedDailyJobs[$name] = $errorMsg;
            return false;
        }
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
