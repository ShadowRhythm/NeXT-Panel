<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - 驗證郵件',
    'verify_email_title' => '電子郵件驗證',
    'verify_code_text' => '您請求的電子郵件驗證碼為: %code%',
    'verify_code_expire' => '本驗證碼在 %expire% 前有效。',
    'verify_code_ignore' => '如果此驗證碼非您本人申請，請忽略此郵件。',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - 重設密碼',
    'password_reset_title' => '密碼重設',
    'password_reset_desc' => '您收到此郵件是因為您在 %app_name% 系統申請了密碼重設，如果非本人申請，請忽略此郵件。',
    'click_to_reset_password' => '點選此連結重設密碼',

    // New User (new_user.tpl)
    'account_created_title' => '帳戶已經建立',

    // Test Email (test.tpl)
    'test_email_subject' => '測試郵件',
    'test_email_title' => '郵件發送測試',
    'test_email_desc' => '這是一封測試郵件。如果您能收到，說明郵件發送設定有效，可以正常運作。',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - 每日流量報告以及公告',
    'traffic_report_title' => '每日流量報告',
    'username_label' => '使用者名稱: %username%',
    'email_label' => '電子郵件: %email%',
    'total_traffic' => '總流量: %traffic%',
    'used_traffic' => '已用流量: %traffic%',
    'remaining_traffic' => '剩餘流量: %traffic%',
    'today_traffic' => '今日使用流量: %traffic%',
    'site_announcement' => '網站公告:',
    'good_night' => '晚安！',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => '財務日報',
    'finance_weekly_title' => '財務週報',
    'finance_monthly_title' => '財務月報',
    'finance_last_week_orders' => '上週總收入筆數：%count%',
    'finance_last_week_total' => '上週總收入金額：%amount%',
    'finance_last_month_orders' => '上月總收入筆數：%count%',
    'finance_last_month_total' => '上月總收入金額：%amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => '系統提示',
    'modify_email_settings' => '修改郵件接收設定',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - 您的帳戶等級已經到期了',
    'free_traffic_reset_subject' => '%app_name% - 免費流量重設通知',
    'free_traffic_reset_body' => '您好，您的免費流量已經被重設為 %bandwidth% GB。',
    'low_traffic_subject' => '%app_name% - 您的剩餘流量過低',
    'low_traffic_body' => '您好，系統發現您的剩餘流量已經低於 %limit% %unit% 。',
    'new_login_subject' => '%app_name% - 新登入通知',
    'new_login_body' => '您的帳戶於 %time% 透過 %ip% 位址登入了使用者面板',
    'new_sub_subject' => '%app_name% - 新訂閱通知',
    'new_sub_body' => '您的帳戶於 %time% 透過 %ip% 位址訂閱了新的節點',
    'ticket_replied_subject' => '%app_name% - 工單已被回覆',
    'ticket_replied_body' => '您好，有人回覆了工單，請您查看。',
    'new_ticket_subject' => '%app_name% - 新工單已被建立',
    'new_ticket_body' => '管理員，有人開啟了新的工單，請您及時處理。',
    'ticket_replied_admin_body' => '管理員，有人回覆了 <a href="%ticket_url%">#%ticket_id%</a> 工單，請你及時處理。',
    'ticket_replied_user_body' => '你好，有人回覆了<a href="%ticket_url%">工單</a>，請你查看。',
    'ticket_ai_replied_user_body' => '你好，AI助理回覆了<a href="%ticket_url%">工單</a>，請你查看。',
    'class_expired_body' => '你好，系統發現你的帳號等級已經過期了。',
    'class_expired_reset_body' => '流量已經被重設為 %bandwidth% GB。',
    'auto_renew_disabled_subject' => '%app_name% - 訂單自動續費已停用',
    'auto_renew_disabled_off_shelf_body' => '您的商品【%product%】(訂單 #%order_id%) 因商品已下架或停售，系統已自動關閉該訂單的自動續費。請及時前往商店選購其他套餐。',
    'auto_renew_failed_subject' => '%app_name% - 訂單自動續費扣款失敗提醒',
    'auto_renew_failed_tabp_body' => '您的商品【%product%】(訂單 #%order_id%) 即將到期，自動續費扣款失敗（需要 %price%，目前帳戶餘額 %balance%）。請在訂單到期前及時儲值，系統會繼續嘗試續費；訂單過期後將不再自動重試。',
    'auto_renew_failed_time_body' => '您的商品【%product%】(訂單 #%order_id%) 自動續費扣款失敗（需要 %price%，目前帳戶餘額 %balance%）。請及時儲值，儲值後系統將自動重試續費。',
    'auto_renew_out_of_stock_subject' => '%app_name% - 訂單自動續費庫存不足提醒',
    'auto_renew_out_of_stock_body' => '您的商品【%product%】(訂單 #%order_id%) 即將到期，因商品暫時售罄無法自動續費。請留意庫存補充或前往商店選購其他套餐。',
    'auto_renew_success_subject' => '%app_name% - 訂單自動續費成功',
    'auto_renew_success_body' => '您的商品【%product%】已成功自動續費，產生新訂單 #%order_id%，扣除餘額 %price%。',
];
