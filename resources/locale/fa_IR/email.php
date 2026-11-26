<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - ایمیل تأیید',
    'verify_email_title' => 'تأیید ایمیل',
    'verify_code_text' => 'کد تأیید ایمیل درخواستی شما: %code%',
    'verify_code_expire' => 'این کد تأیید تا %expire% معتبر است.',
    'verify_code_ignore' => 'اگر شما این کد را درخواست نکرده‌اید، لطفاً این ایمیل را نادیده بگیرید.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - بازنشانی رمز عبور',
    'password_reset_title' => 'بازنشانی رمز عبور',
    'password_reset_desc' => 'شما این ایمیل را دریافت کرده‌اید زیرا درخواست بازنشانی رمز عبور در %app_name% ثبت کرده‌اید. اگر این درخواست از سوی شما نبوده است، لطفاً این ایمیل را نادیده بگیرید.',
    'click_to_reset_password' => 'برای بازنشانی رمز عبور خود اینجا کلیک کنید',

    // New User (new_user.tpl)
    'account_created_title' => 'حساب کاربری ایجاد شد',

    // Test Email (test.tpl)
    'test_email_subject' => 'ایمیل آزمایشی',
    'test_email_title' => 'آزمایش ارسال ایمیل',
    'test_email_desc' => 'این یک ایمیل آزمایشی است. اگر این ایمیل را دریافت کرده‌اید، پیکربندی ایمیل شما به‌درستی کار می‌کند.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - گزارش ترافیک روزانه و اطلاعیه‌ها',
    'traffic_report_title' => 'گزارش ترافیک روزانه',
    'username_label' => 'نام کاربری: %username%',
    'email_label' => 'ایمیل: %email%',
    'total_traffic' => 'کل ترافیک: %traffic%',
    'used_traffic' => 'ترافیک مصرف‌شده: %traffic%',
    'remaining_traffic' => 'ترافیک باقی‌مانده: %traffic%',
    'today_traffic' => 'ترافیک امروز: %traffic%',
    'site_announcement' => 'اطلاعیه سایت:',
    'good_night' => 'شب خوش!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'گزارش مالی روزانه',
    'finance_weekly_title' => 'گزارش مالی هفتگی',
    'finance_monthly_title' => 'گزارش مالی ماهانه',
    'finance_last_week_orders' => 'تعداد کل سفارش‌های هفته گذشته: %count%',
    'finance_last_week_total' => 'مجموع درآمد هفته گذشته: %amount%',
    'finance_last_month_orders' => 'تعداد کل سفارش‌های ماه گذشته: %count%',
    'finance_last_month_total' => 'مجموع درآمد ماه گذشته: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'اعلان سیستم',
    'modify_email_settings' => 'ویرایش تنظیمات ایمیل',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - سطح حساب کاربری شما منقضی شده است',
    'free_traffic_reset_subject' => '%app_name% - اطلاعیه بازنشانی ترافیک رایگان',
    'free_traffic_reset_body' => 'سلام، ترافیک رایگان شما به %bandwidth% GB بازنشانی شده است.',
    'low_traffic_subject' => '%app_name% - ترافیک باقی‌مانده شما کم است',
    'low_traffic_body' => 'سلام، سیستم متوجه شد که ترافیک باقی‌مانده شما کمتر از %limit% %unit% است.',
    'new_login_subject' => '%app_name% - اعلان ورود جدید',
    'new_login_body' => 'حساب کاربری شما در تاریخ %time% از نشانی IP %ip% وارد پنل کاربری شد',
    'new_sub_subject' => '%app_name% - اعلان اشتراک جدید',
    'new_sub_body' => 'حساب کاربری شما در تاریخ %time% از نشانی IP %ip% اشتراک جدید دریافت کرد',
    'ticket_replied_subject' => '%app_name% - به تیکت پاسخ داده شد',
    'ticket_replied_body' => 'سلام، پاسخی به تیکت شما ارسال شده است؛ لطفاً آن را بررسی کنید.',
    'new_ticket_subject' => '%app_name% - تیکت جدید ایجاد شد',
    'new_ticket_body' => 'مدیر گرامی، کاربری تیکت جدیدی باز کرده است؛ لطفاً در اسرع وقت رسیدگی فرمایید.',
    'ticket_replied_admin_body' => 'مدیر گرامی، کاربری به تیکت <a href="%ticket_url%">#%ticket_id%</a> پاسخ داده است؛ لطفاً در اسرع وقت رسیدگی فرمایید.',
    'ticket_replied_user_body' => 'سلام، پاسخی به <a href="%ticket_url%">تیکت</a> شما ارسال شده است؛ لطفاً آن را بررسی کنید.',
    'ticket_ai_replied_user_body' => 'سلام، دستیار هوش مصنوعی به <a href="%ticket_url%">تیکت</a> شما پاسخ داده است؛ لطفاً آن را بررسی کنید.',
    'class_expired_body' => 'سلام، سیستم متوجه شد که سطح حساب کاربری شما منقضی شده است.',
    'class_expired_reset_body' => 'ترافیک شما به %bandwidth% GB بازنشانی شده است.',
    'auto_renew_disabled_subject' => '%app_name% - تمدید خودکار سفارش غیرفعال شد',
    'auto_renew_disabled_off_shelf_body' => 'محصول [%product%] (سفارش #%order_id%) از فروشگاه برداشته یا متوقف شده است؛ بنابراین تمدید خودکار آن به‌طور خودکار غیرفعال شد. لطفاً برای انتخاب بسته دیگر به فروشگاه مراجعه کنید.',
    'auto_renew_failed_subject' => '%app_name% - پرداخت تمدید خودکار سفارش ناموفق بود',
    'auto_renew_failed_tabp_body' => 'محصول [%product%] (سفارش #%order_id%) در آستانه انقضا است و کسر وجه برای تمدید خودکار ناموفق بود (مبلغ مورد نیاز %price%، موجودی فعلی %balance%). لطفاً پیش از انقضا حساب خود را شارژ کنید تا سیستم دوباره تلاش کند؛ پس از انقضا تلاش مجددی انجام نخواهد شد.',
    'auto_renew_failed_time_body' => 'کسر وجه برای تمدید خودکار محصول [%product%] (سفارش #%order_id%) ناموفق بود (مبلغ مورد نیاز %price%، موجودی فعلی %balance%). لطفاً به‌موقع حساب خود را شارژ کنید تا سیستم به‌طور خودکار دوباره تلاش کند.',
    'auto_renew_out_of_stock_subject' => '%app_name% - اتمام موجودی محصول برای تمدید خودکار سفارش',
    'auto_renew_out_of_stock_body' => 'محصول [%product%] (سفارش #%order_id%) در آستانه انقضا است و تمدید خودکار به دلیل ناموجود بودن موقت محصول ناموفق بود. لطفاً بعداً بررسی کنید یا بسته دیگری از فروشگاه انتخاب نمایید.',
    'auto_renew_success_subject' => '%app_name% - تمدید خودکار سفارش با موفقیت انجام شد',
    'auto_renew_success_body' => 'محصول [%product%] شما به‌طور خودکار تمدید شد. سفارش جدید #%order_id% ایجاد گردید و مبلغ %price% از موجودی شما کسر شد.',
];
