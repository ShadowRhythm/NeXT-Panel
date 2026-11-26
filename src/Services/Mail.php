<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Config;
use App\Models\User;
use App\Services\Mail\AlibabaCloud;
use App\Services\Mail\Mailchimp;
use App\Services\Mail\Mailgun;
use App\Services\Mail\NullMail;
use App\Services\Mail\Postal;
use App\Services\Mail\Postmark;
use App\Services\Mail\Resend;
use App\Services\Mail\SendGrid;
use App\Services\Mail\Ses;
use App\Services\Mail\Smtp;
use Psr\Http\Client\ClientExceptionInterface;
use SendGrid\Mail\TypeException;
use Smarty\Exception;
use Smarty\Smarty;

/*
 * Mail Service
 */
final class Mail
{
    public static function getClient(): AlibabaCloud|Mailchimp|Mailgun|NullMail|Postal|Postmark|Resend|SendGrid|Ses|Smtp
    {
        return match (Config::obtain('email_driver')) {
            'alibabacloud' => new AlibabaCloud(),
            'mailchimp' => new Mailchimp(),
            'mailgun' => new Mailgun(),
            'postal' => new Postal(),
            'resend' => new Resend(),
            'sendgrid' => new SendGrid(),
            'ses' => new Ses(),
            'smtp' => new Smtp(),
            'postmark' => new Postmark(),
            default => new NullMail(),
        };
    }

    /**
     * @throws Exception
     */
    public static function genHtml($template, $ary, ?string $locale = null): false|string
    {
        $smarty = new Smarty();
        $smarty->setTemplateDir(BASE_PATH . '/resources/email/');
        $smarty->setCompileDir(BASE_PATH . '/storage/framework/smarty/compile/');
        $smarty->setCacheDir(BASE_PATH . '/storage/framework/smarty/cache/');
        $smarty->assign('config', View::getConfig());

        $currentLocale = $locale;
        if ($currentLocale === null && is_array($ary)) {
            $currentLocale = $ary['locale'] ?? null;
            if ($currentLocale === null && isset($ary['user']) && $ary['user'] instanceof User) {
                $currentLocale = $ary['user']->locale;
            }
        } elseif ($currentLocale === null && is_object($ary)) {
            $currentLocale = $ary->locale ?? null;
            if ($currentLocale === null && isset($ary->user) && $ary->user instanceof User) {
                $currentLocale = $ary->user->locale;
            }
        }
        $currentLocale = $currentLocale ?: I18n::getCurrentLocale();
        $smarty->assign('current_locale', $currentLocale);
        $smarty->assign('is_rtl', I18n::isRtlLocale($currentLocale));

        View::registerSmartyI18n($smarty, $currentLocale);

        foreach ($ary as $key => $value) {
            $smarty->assign($key, $value);
        }

        return $smarty->fetch($template);
    }

    /**
     * @throws Exception
     * @throws ClientExceptionInterface
     * @throws TypeException
     */
    public static function send($to, $subject, $template, $array = [], ?string $locale = null): void
    {
        $body = self::genHtml($template, $array, $locale);

        self::getClient()->send($to, $subject, $body);
    }
}
