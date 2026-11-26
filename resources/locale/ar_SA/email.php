<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - رسالة التحقق',
    'verify_email_title' => 'التحقق من البريد الإلكتروني',
    'verify_code_text' => 'رمز التحقق المطلوب لبريدك الإلكتروني هو: %code%',
    'verify_code_expire' => 'رمز التحقق هذا صالح حتى %expire%.',
    'verify_code_ignore' => 'إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - إعادة تعيين كلمة المرور',
    'password_reset_title' => 'إعادة تعيين كلمة المرور',
    'password_reset_desc' => 'تلقيت هذه الرسالة لأنك طلبت إعادة تعيين كلمة المرور في %app_name%. إذا لم تطلب ذلك بنفسك، يرجى تجاهل هذه الرسالة.',
    'click_to_reset_password' => 'انقر هنا لإعادة تعيين كلمة المرور',

    // New User (new_user.tpl)
    'account_created_title' => 'تم إنشاء الحساب',

    // Test Email (test.tpl)
    'test_email_subject' => 'رسالة بريد إلكتروني تجريبية',
    'test_email_title' => 'اختبار إرسال البريد الإلكتروني',
    'test_email_desc' => 'هذه رسالة بريد إلكتروني تجريبية. إذا وصلتك هذه الرسالة، فإن إعدادات البريد الإلكتروني تعمل بشكل صحيح.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - التقرير اليومي للبيانات والإعلانات',
    'traffic_report_title' => 'التقرير اليومي للبيانات',
    'username_label' => 'اسم المستخدم: %username%',
    'email_label' => 'البريد الإلكتروني: %email%',
    'total_traffic' => 'إجمالي البيانات: %traffic%',
    'used_traffic' => 'البيانات المستخدمة: %traffic%',
    'remaining_traffic' => 'البيانات المتبقية: %traffic%',
    'today_traffic' => 'استهلاك اليوم: %traffic%',
    'site_announcement' => 'إعلان الموقع:',
    'good_night' => 'تصبح على خير!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'التقرير المالي اليومي',
    'finance_weekly_title' => 'التقرير المالي الأسبوعي',
    'finance_monthly_title' => 'التقرير المالي الشهري',
    'finance_last_week_orders' => 'إجمالي طلبات الأسبوع الماضي: %count%',
    'finance_last_week_total' => 'إجمالي دخل الأسبوع الماضي: %amount%',
    'finance_last_month_orders' => 'إجمالي طلبات الشهر الماضي: %count%',
    'finance_last_month_total' => 'إجمالي دخل الشهر الماضي: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'إشعار النظام',
    'modify_email_settings' => 'تعديل إعدادات البريد الإلكتروني',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - انتهت صلاحية مستوى حسابك',
    'free_traffic_reset_subject' => '%app_name% - إشعار إعادة تعيين البيانات المجانية',
    'free_traffic_reset_body' => 'مرحبًا، تمت إعادة تعيين بياناتك المجانية إلى %bandwidth% GB.',
    'low_traffic_subject' => '%app_name% - رصيد بياناتك المتبقي منخفض',
    'low_traffic_body' => 'مرحبًا، لاحظ النظام أن رصيد بياناتك المتبقي أقل من %limit% %unit%.',
    'new_login_subject' => '%app_name% - إشعار تسجيل دخول جديد',
    'new_login_body' => 'تم تسجيل الدخول إلى حسابك في لوحة المستخدم من عنوان IP %ip% في %time%',
    'new_sub_subject' => '%app_name% - إشعار اشتراك جديد',
    'new_sub_body' => 'تم طلب اشتراك من حسابك من عنوان IP %ip% في %time%',
    'ticket_replied_subject' => '%app_name% - تم الرد على التذكرة',
    'ticket_replied_body' => 'مرحبًا، قام أحدهم بالرد على تذكرتك، يرجى التحقق منها.',
    'new_ticket_subject' => '%app_name% - تم إنشاء تذكرة جديدة',
    'new_ticket_body' => 'أيها المسؤول، قام أحد المستخدمين بفتح تذكرة جديدة، يرجى معالجتها في الوقت المناسب.',
    'ticket_replied_admin_body' => 'أيها المسؤول، قام أحد المستخدمين بالرد على التذكرة <a href="%ticket_url%">#%ticket_id%</a>، يرجى معالجتها في الوقت المناسب.',
    'ticket_replied_user_body' => 'مرحبًا، قام أحدهم بالرد على <a href="%ticket_url%">تذكرتك</a>، يرجى التحقق منها.',
    'ticket_ai_replied_user_body' => 'مرحبًا، قام المساعد الذكي بالرد على <a href="%ticket_url%">تذكرتك</a>، يرجى التحقق منها.',
    'class_expired_body' => 'مرحبًا، لاحظ النظام أن صلاحية مستوى حسابك قد انتهت.',
    'class_expired_reset_body' => 'تمت إعادة تعيين بياناتك إلى %bandwidth% GB.',
    'auto_renew_disabled_subject' => '%app_name% - تم تعطيل التجديد التلقائي للطلب',
    'auto_renew_disabled_off_shelf_body' => 'تم إيقاف أو سحب المنتج [%product%] (الطلب #%order_id%) من المتجر، ولذلك تم تعطيل التجديد التلقائي له تلقائيًا. يرجى زيارة المتجر لاختيار باقة أخرى.',
    'auto_renew_failed_subject' => '%app_name% - فشل الدفع للتجديد التلقائي للطلب',
    'auto_renew_failed_tabp_body' => 'أوشكت صلاحية منتجك [%product%] (الطلب #%order_id%) على الانتهاء، وفشل خصم التجديد التلقائي (المطلوب %price%، الرصيد الحالي %balance%). يرجى الشحن في الوقت المناسب قبل انتهاء الصلاحية ليتمكن النظام من إعادة المحاولة؛ ولن تتم أي محاولات بعد انتهاء الصلاحية.',
    'auto_renew_failed_time_body' => 'فشل خصم التجديد التلقائي لمنتجك [%product%] (الطلب #%order_id%) (المطلوب %price%، الرصيد الحالي %balance%). يرجى الشحن في الوقت المناسب وسيعيد النظام المحاولة تلقائيًا.',
    'auto_renew_out_of_stock_subject' => '%app_name% - نفاد مخزون التجديد التلقائي للطلب',
    'auto_renew_out_of_stock_body' => 'أوشكت صلاحية منتجك [%product%] (الطلب #%order_id%) على الانتهاء، وفشل التجديد التلقائي لأن المنتج غير متوفر مؤقتًا في المخزون. يرجى المراجعة لاحقًا أو اختيار باقة أخرى من المتجر.',
    'auto_renew_success_subject' => '%app_name% - نجح التجديد التلقائي للطلب',
    'auto_renew_success_body' => 'تم تجديد منتجك [%product%] تلقائيًا. تم إنشاء الطلب الجديد #%order_id% وتم خصم %price% من رصيدك.',
];
