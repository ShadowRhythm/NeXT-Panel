<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - 验证邮件',
    'verify_email_title' => '邮箱验证',
    'verify_code_text' => '你请求的邮箱验证代码为: %code%',
    'verify_code_expire' => '本验证代码在 %expire% 前有效。',
    'verify_code_ignore' => '如果此验证码非你本人申请，请忽视此邮件。',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - 重置密码',
    'password_reset_title' => '密码重置',
    'password_reset_desc' => '你收到此邮件是因为你在 %app_name% 系统申请了密码重置，如果非本人申请，请忽略此邮件。',
    'click_to_reset_password' => '点击此链接重置密码',

    // New User (new_user.tpl)
    'account_created_title' => '账户已经生成',

    // Test Email (test.tpl)
    'test_email_subject' => '测试邮件',
    'test_email_title' => '邮件发送测试',
    'test_email_desc' => '这是一封测试邮件。如果你能收到，说明邮件发送配置有效，可以正常工作。',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - 每日流量报告以及公告',
    'traffic_report_title' => '每日流量报告',
    'username_label' => '用户名: %username%',
    'email_label' => 'Email: %email%',
    'total_traffic' => '总流量: %traffic%',
    'used_traffic' => '已用流量: %traffic%',
    'remaining_traffic' => '剩余流量: %traffic%',
    'today_traffic' => '今日使用流量: %traffic%',
    'site_announcement' => '站点公告:',
    'good_night' => '晚安！',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => '财务日报',
    'finance_weekly_title' => '财务周报',
    'finance_monthly_title' => '财务月报',
    'finance_last_week_orders' => '上周总收入笔数：%count%',
    'finance_last_week_total' => '上周总收入金额：%amount%',
    'finance_last_month_orders' => '上月总收入笔数：%count%',
    'finance_last_month_total' => '上月总收入金额：%amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => '系统提示',
    'modify_email_settings' => '修改邮件接收设置',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - 你的账号等级已经过期了',
    'free_traffic_reset_subject' => '%app_name% - 免费流量重置通知',
    'free_traffic_reset_body' => '你好，你的免费流量已经被重置为 %bandwidth% GB。',
    'low_traffic_subject' => '%app_name% - 你的剩余流量过低',
    'low_traffic_body' => '你好，系统发现你剩余流量已经低于 %limit% %unit% 。',
    'new_login_subject' => '%app_name% - 新登录通知',
    'new_login_body' => '你的账号于 %time% 通过 %ip% 地址登录了用户面板',
    'new_sub_subject' => '%app_name% - 新订阅通知',
    'new_sub_body' => '你的账号于 %time% 通过 %ip% 地址订阅了新的节点',
    'ticket_replied_subject' => '%app_name% - 工单被回复',
    'ticket_replied_body' => '你好，有人回复了工单，请你查看。',
    'new_ticket_subject' => '%app_name% - 新工单被开启',
    'new_ticket_body' => '管理员，有人开启了新的工单，请你及时处理。',
    'ticket_replied_admin_body' => '管理员，有人回复了 <a href="%ticket_url%">#%ticket_id%</a> 工单，请你及时处理。',
    'ticket_replied_user_body' => '你好，有人回复了<a href="%ticket_url%">工单</a>，请你查看。',
    'ticket_ai_replied_user_body' => '你好，AI助理回复了<a href="%ticket_url%">工单</a>，请你查看。',
    'class_expired_body' => '你好，系统发现你的账号等级已经过期了。',
    'class_expired_reset_body' => '流量已经被重置为 %bandwidth% GB。',
    'auto_renew_disabled_subject' => '%app_name% - 订单自动续费已停用',
    'auto_renew_disabled_off_shelf_body' => '您的商品【%product%】(订单 #%order_id%) 因商品已下架或停售，系统已自动关闭该订单的自动续费。请及时前往商城选购其他套餐。',
    'auto_renew_failed_subject' => '%app_name% - 订单自动续费扣款失败提醒',
    'auto_renew_failed_tabp_body' => '您的商品【%product%】(订单 #%order_id%) 即将到期，自动续费扣款失败（需要 %price%，当前账户余额 %balance%）。请在订单到期前及时充值，系统会继续尝试续费；订单过期后将不再自动重试。',
    'auto_renew_failed_time_body' => '您的商品【%product%】(订单 #%order_id%) 自动续费扣款失败（需要 %price%，当前账户余额 %balance%）。请及时充值，充值后系统将自动重试续费。',
    'auto_renew_out_of_stock_subject' => '%app_name% - 订单自动续费库存不足提醒',
    'auto_renew_out_of_stock_body' => '您的商品【%product%】(订单 #%order_id%) 即将到期，因商品暂时售罄无法自动续费。请留意库存补充或前往商城选购其他套餐。',
    'auto_renew_success_subject' => '%app_name% - 订单自动续费成功',
    'auto_renew_success_body' => '您的商品【%product%】已成功自动续费，生成新订单 #%order_id%，扣除余额 %price%。',
];
