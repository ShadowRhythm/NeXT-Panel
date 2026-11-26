<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - 認証メール',
    'verify_email_title' => 'メールアドレス認証',
    'verify_code_text' => 'リクエストされたメール認証コードは次の通りです: %code%',
    'verify_code_expire' => 'この認証コードの有効期限は %expire% までです。',
    'verify_code_ignore' => '本コードにお心当たりがない場合は、このメールを破棄してください。',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - パスワード再設定',
    'password_reset_title' => 'パスワードの再設定',
    'password_reset_desc' => '%app_name% にてパスワード再設定のリクエストを受け付けたため、本メールをお送りしています。お心当たりがない場合は、このメールを破棄してください。',
    'click_to_reset_password' => 'こちらのリンクをクリックしてパスワードを再設定してください',

    // New User (new_user.tpl)
    'account_created_title' => 'アカウントが作成されました',

    // Test Email (test.tpl)
    'test_email_subject' => 'テストメール',
    'test_email_title' => 'メール送信テスト',
    'test_email_desc' => 'これはテストメールです。このメールが届いている場合、メール送信設定は正常に機能しています。',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - 本日のデータ通信量レポートとお知らせ',
    'traffic_report_title' => '日次通信量レポート',
    'username_label' => 'ユーザー名: %username%',
    'email_label' => 'メールアドレス: %email%',
    'total_traffic' => '総通信量: %traffic%',
    'used_traffic' => '使用済み通信量: %traffic%',
    'remaining_traffic' => '残り通信量: %traffic%',
    'today_traffic' => '本日の使用通信量: %traffic%',
    'site_announcement' => 'サイトからのお知らせ:',
    'good_night' => '良い夜をお過ごしください！',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => '財務日報',
    'finance_weekly_title' => '財務週報',
    'finance_monthly_title' => '財務月報',
    'finance_last_week_orders' => '先週の注文数: %count% 件',
    'finance_last_week_total' => '先週の総売上: %amount%',
    'finance_last_month_orders' => '先月の注文数: %count% 件',
    'finance_last_month_total' => '先月の総売上: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'システムからのお知らせ',
    'modify_email_settings' => 'メール受信設定の変更',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - アカウントレベルの有効期限が切れました',
    'free_traffic_reset_subject' => '%app_name% - 無料通信量リセットのお知らせ',
    'free_traffic_reset_body' => 'こんにちは。無料データ通信量が %bandwidth% GB にリセットされました。',
    'low_traffic_subject' => '%app_name% - 残り通信量が少なくなっています',
    'low_traffic_body' => 'こんにちは。残りデータ通信量が %limit% %unit% 未満になりましたのでお知らせします。',
    'new_login_subject' => '%app_name% - 新規ログイン通知',
    'new_login_body' => 'お客様のアカウントが %time% に IPアドレス %ip% からユーザーパネルにログインしました。',
    'new_sub_subject' => '%app_name% - 新規サブスクリプション通知',
    'new_sub_body' => 'お客様のアカウントが %time% に IPアドレス %ip% からノードを購読しました。',
    'ticket_replied_subject' => '%app_name% - チケットに返信がありました',
    'ticket_replied_body' => 'こんにちは。チケットへの返信がありましたのでご確認ください。',
    'new_ticket_subject' => '%app_name% - 新しいチケットが作成されました',
    'new_ticket_body' => '管理者様：新しいチケットが作成されました。早めの対応をお願いします。',
    'ticket_replied_admin_body' => '管理者様、チケット <a href="%ticket_url%">#%ticket_id%</a> に返信がありました。速やかに対応してください。',
    'ticket_replied_user_body' => 'こんにちは、<a href="%ticket_url%">チケット</a>に返信がありましたのでご確認ください。',
    'ticket_ai_replied_user_body' => 'こんにちは、AIアシスタントが<a href="%ticket_url%">チケット</a>に返信しましたのでご確認ください。',
    'class_expired_body' => 'アカウントレベルの有効期限が切れました。',
    'class_expired_reset_body' => 'データ通信量は %bandwidth% GB にリセットされました。',
    'auto_renew_disabled_subject' => '%app_name% - 注文の自動更新が無効になりました',
    'auto_renew_disabled_off_shelf_body' => '商品【%product%】(注文 #%order_id%) は販売終了となったため、自動更新が自動的に無効化されました。ショップにて他のプランをご検討ください。',
    'auto_renew_failed_subject' => '%app_name% - 自動更新の引き落とし失敗',
    'auto_renew_failed_tabp_body' => '商品【%product%】(注文 #%order_id%) の期限が近づいていますが、自動更新の引き落としに失敗しました（必要額 %price%、現在残高 %balance%）。期限前に残高をチャージしてください。期限切れ後は再試行されません。',
    'auto_renew_failed_time_body' => '商品【%product%】(注文 #%order_id%) の自動更新の引き落としに失敗しました（必要額 %price%、現在残高 %balance%）。残高をチャージすると自動的に再試行されます。',
    'auto_renew_out_of_stock_subject' => '%app_name% - 自動更新の在庫不足通知',
    'auto_renew_out_of_stock_body' => '商品【%product%】(注文 #%order_id%) の期限が近づいていますが、在庫切れのため自動更新できませんでした。ショップにて他のプランをご確認ください。',
    'auto_renew_success_subject' => '%app_name% - 自動更新が完了しました',
    'auto_renew_success_body' => '商品【%product%】の自動更新が完了し、新しい注文 #%order_id% が作成されました（引き落とし額 %price%）。',
];
