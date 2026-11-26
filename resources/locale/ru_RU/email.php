<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - Подтверждение электронной почты',
    'verify_email_title' => 'Подтверждение почты',
    'verify_code_text' => 'Ваш код подтверждения почты: %code%',
    'verify_code_expire' => 'Этот код подтверждения действителен до %expire%.',
    'verify_code_ignore' => 'Если вы не запрашивали этот код, просто проигнорируйте это письмо.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - Сброс пароля',
    'password_reset_title' => 'Сброс пароля',
    'password_reset_desc' => 'Вы получили это письмо, потому что запросили сброс пароля на %app_name%. Если вы этого не делали, просто проигнорируйте письмо.',
    'click_to_reset_password' => 'Нажмите здесь, чтобы сбросить пароль',

    // New User (new_user.tpl)
    'account_created_title' => 'Аккаунт создан',

    // Test Email (test.tpl)
    'test_email_subject' => 'Тестовое письмо',
    'test_email_title' => 'Проверка отправки почты',
    'test_email_desc' => 'Это тестовое письмо. Если вы его получили, настройки отправки почты работают правильно.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - Ежедневный отчет о трафике и объявления',
    'traffic_report_title' => 'Ежедневный отчет о трафике',
    'username_label' => 'Имя пользователя: %username%',
    'email_label' => 'Email: %email%',
    'total_traffic' => 'Всего трафика: %traffic%',
    'used_traffic' => 'Использовано: %traffic%',
    'remaining_traffic' => 'Осталось: %traffic%',
    'today_traffic' => 'Использовано сегодня: %traffic%',
    'site_announcement' => 'Объявление сайта:',
    'good_night' => 'Спокойной ночи!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'Ежедневный финансовый отчет',
    'finance_weekly_title' => 'Еженедельный финансовый отчет',
    'finance_monthly_title' => 'Ежемесячный финансовый отчет',
    'finance_last_week_orders' => 'Всего заказов за прошлую неделю: %count%',
    'finance_last_week_total' => 'Общий доход за прошлую неделю: %amount%',
    'finance_last_month_orders' => 'Всего заказов за прошлый месяц: %count%',
    'finance_last_month_total' => 'Общий доход за прошлый месяц: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'Системное уведомление',
    'modify_email_settings' => 'Настройки почтовых уведомлений',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - Срок действия уровня вашего аккаунта истек',
    'free_traffic_reset_subject' => '%app_name% - Уведомление о сбросе бесплатного трафика',
    'free_traffic_reset_body' => 'Здравствуйте! Ваш бесплатный трафик был сброшен до %bandwidth% ГБ.',
    'low_traffic_subject' => '%app_name% - Заканчивается трафик',
    'low_traffic_body' => 'Здравствуйте! Система обнаружила, что ваш остаток трафика менее %limit% %unit%.',
    'new_login_subject' => '%app_name% - Вход в аккаунт',
    'new_login_body' => 'В ваш аккаунт был совершен вход с IP %ip% в %time%',
    'new_sub_subject' => '%app_name% - Запрос подписки',
    'new_sub_body' => 'С вашего аккаунта была запрошена подписка с IP %ip% в %time%',
    'ticket_replied_subject' => '%app_name% - Ответ на тикет',
    'ticket_replied_body' => 'Здравствуйте! В вашем тикете появился новый ответ, пожалуйста, проверьте его.',
    'new_ticket_subject' => '%app_name% - Создан новый тикет',
    'new_ticket_body' => 'Администратор, пользователь создал новый тикет, пожалуйста, обработайте его своевременно.',
    'ticket_replied_admin_body' => 'Администратор, кто-то ответил на тикет <a href="%ticket_url%">#%ticket_id%</a>, пожалуйста, обработайте его своевременно.',
    'ticket_replied_user_body' => 'Здравствуйте, кто-то ответил на ваш <a href="%ticket_url%">тикет</a>, пожалуйста, проверьте.',
    'ticket_ai_replied_user_body' => 'Здравствуйте, ИИ-помощник ответил на ваш <a href="%ticket_url%">тикет</a>, пожалуйста, проверьте.',
    'class_expired_body' => 'Здравствуйте! Срок действия вашего уровня аккаунта истек.',
    'class_expired_reset_body' => 'Ваш лимит трафика сброшен до %bandwidth% ГБ.',
    'auto_renew_disabled_subject' => '%app_name% - Автопродление заказа отключено',
    'auto_renew_disabled_off_shelf_body' => 'Товар [%product%] (Заказ #%order_id%) снят с продажи, поэтому автопродление было автоматически отключено. Пожалуйста, выберите другой тариф в магазине.',
    'auto_renew_failed_subject' => '%app_name% - Ошибка списания при автопродлении',
    'auto_renew_failed_tabp_body' => 'Срок действия [%product%] (Заказ #%order_id%) истекает, списание для автопродления не удалось (требуется %price%, текущий баланс %balance%). Пожалуйста, пополните баланс до истечения срока для повторной попытки.',
    'auto_renew_failed_time_body' => 'Не удалось списать средства для автопродления [%product%] (Заказ #%order_id%) (требуется %price%, текущий баланс %balance%). Пополните баланс, и система повторит попытку автоматически.',
    'auto_renew_out_of_stock_subject' => '%app_name% - Недостаточно товара на складе для автопродления',
    'auto_renew_out_of_stock_body' => 'Срок действия [%product%] (Заказ #%order_id%) истекает, автопродление невозможно, так как товар временно закончился. Пожалуйста, выберите другой тариф в магазине.',
    'auto_renew_success_subject' => '%app_name% - Автопродление заказа выполнено',
    'auto_renew_success_body' => 'Ваш тариф [%product%] успешно продлен. Создан новый заказ #%order_id%, списано с баланса: %price%.',
];
