<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - Doğrulama E-postası',
    'verify_email_title' => 'E-posta Doğrulama',
    'verify_code_text' => 'Talep ettiğiniz e-posta doğrulama kodu: %code%',
    'verify_code_expire' => 'Bu doğrulama kodu %expire% tarihine kadar geçerlidir.',
    'verify_code_ignore' => 'Bu kodu siz talep etmediyseniz lütfen bu e-postayı dikkate almayın.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - Parola Sıfırlama',
    'password_reset_title' => 'Parola Sıfırlama',
    'password_reset_desc' => 'Bu e-postayı %app_name% üzerinde parola sıfırlama talebinde bulunduğunuz için aldınız. Bu talebi siz oluşturmadıysanız lütfen bu e-postayı dikkate almayın.',
    'click_to_reset_password' => 'Parolanızı sıfırlamak için buraya tıklayın',

    // New User (new_user.tpl)
    'account_created_title' => 'Hesap Oluşturuldu',

    // Test Email (test.tpl)
    'test_email_subject' => 'Test E-postası',
    'test_email_title' => 'E-posta Gönderim Testi',
    'test_email_desc' => 'Bu bir test e-postasıdır. Bu mesajı alıyorsanız e-posta yapılandırmanız düzgün çalışıyor demektir.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - Günlük Trafik Raporu ve Duyurular',
    'traffic_report_title' => 'Günlük Trafik Raporu',
    'username_label' => 'Kullanıcı Adı: %username%',
    'email_label' => 'E-posta: %email%',
    'total_traffic' => 'Toplam Trafik: %traffic%',
    'used_traffic' => 'Kullanılan Trafik: %traffic%',
    'remaining_traffic' => 'Kalan Trafik: %traffic%',
    'today_traffic' => 'Bugünkü Trafik: %traffic%',
    'site_announcement' => 'Site Duyurusu:',
    'good_night' => 'İyi geceler!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'Günlük Finansal Rapor',
    'finance_weekly_title' => 'Haftalık Finansal Rapor',
    'finance_monthly_title' => 'Aylık Finansal Rapor',
    'finance_last_week_orders' => 'Geçen haftaki toplam sipariş: %count%',
    'finance_last_week_total' => 'Geçen haftaki toplam gelir: %amount%',
    'finance_last_month_orders' => 'Geçen ayki toplam sipariş: %count%',
    'finance_last_month_total' => 'Geçen ayki toplam gelir: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'Sistem Bildirimi',
    'modify_email_settings' => 'E-posta Ayarlarını Düzenle',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - Hesap seviyenizin süresi doldu',
    'free_traffic_reset_subject' => '%app_name% - Ücretsiz Trafik Sıfırlama Bildirimi',
    'free_traffic_reset_body' => 'Merhaba, ücretsiz trafiğiniz %bandwidth% GB olarak sıfırlandı.',
    'low_traffic_subject' => '%app_name% - Kalan trafiğiniz azaldı',
    'low_traffic_body' => 'Merhaba, sistem kalan trafiğinizin %limit% %unit% değerinin altına düştüğünü tespit etti.',
    'new_login_subject' => '%app_name% - Yeni Giriş Bildirimi',
    'new_login_body' => 'Hesabınıza %time% tarihinde %ip% IP adresinden kullanıcı paneline giriş yapıldı',
    'new_sub_subject' => '%app_name% - Yeni Abonelik Bildirimi',
    'new_sub_body' => 'Hesabınızdan %time% tarihinde %ip% IP adresi üzerinden abonelik alındı',
    'ticket_replied_subject' => '%app_name% - Destek Talebi Yanıtlandı',
    'ticket_replied_body' => 'Merhaba, destek talebinize yanıt verildi, lütfen kontrol edin.',
    'new_ticket_subject' => '%app_name% - Yeni Destek Talebi Oluşturuldu',
    'new_ticket_body' => 'Yönetici, yeni bir destek talebi açıldı, lütfen zamanında ilgilenin.',
    'ticket_replied_admin_body' => 'Yönetici, <a href="%ticket_url%">#%ticket_id%</a> numaralı destek talebine yanıt verildi, lütfen zamanında ilgilenin.',
    'ticket_replied_user_body' => 'Merhaba, <a href="%ticket_url%">destek talebinize</a> yanıt verildi, lütfen kontrol edin.',
    'ticket_ai_replied_user_body' => 'Merhaba, Yapay Zeka Asistanı <a href="%ticket_url%">destek talebinizi</a> yanıtladı, lütfen kontrol edin.',
    'class_expired_body' => 'Merhaba, sistem hesap seviyenizin süresinin dolduğunu tespit etti.',
    'class_expired_reset_body' => 'Trafiğiniz %bandwidth% GB olarak sıfırlandı.',
    'auto_renew_disabled_subject' => '%app_name% - Sipariş Otomatik Yenilemesi Devre Dışı Bırakıldı',
    'auto_renew_disabled_off_shelf_body' => '[%product%] ürününüz (Sipariş #%order_id%) satıştan kaldırıldığı veya sonlandırıldığı için otomatik yenileme otomatik olarak devre dışı bırakıldı. Başka bir paket seçmek için lütfen mağazayı ziyaret edin.',
    'auto_renew_failed_subject' => '%app_name% - Sipariş Otomatik Yenileme Ödemesi Başarısız Oldu',
    'auto_renew_failed_tabp_body' => '[%product%] ürününüzün (Sipariş #%order_id%) süresi dolmak üzere ve otomatik yenileme tahsilatı başarısız oldu (gereken tutar %price%, mevcut bakiye %balance%). Sistemin yeniden deneyebilmesi için lütfen süresi dolmadan önce bakiye yükleyin; süre dolduktan sonra yeniden deneme yapılmayacaktır.',
    'auto_renew_failed_time_body' => '[%product%] ürününüzün (Sipariş #%order_id%) otomatik yenileme tahsilatı başarısız oldu (gereken tutar %price%, mevcut bakiye %balance%). Lütfen zamanında bakiye yükleyin, sistem otomatik olarak yeniden deneyecektir.',
    'auto_renew_out_of_stock_subject' => '%app_name% - Sipariş Otomatik Yenileme Stokta Yok',
    'auto_renew_out_of_stock_body' => '[%product%] ürününüzün (Sipariş #%order_id%) süresi dolmak üzere ve ürün geçici olarak stokta olmadığı için otomatik yenileme başarısız oldu. Lütfen daha sonra tekrar kontrol edin veya mağazadan başka bir paket seçin.',
    'auto_renew_success_subject' => '%app_name% - Sipariş Otomatik Yenilemesi Başarılı',
    'auto_renew_success_body' => '[%product%] ürününüz otomatik olarak yenilendi. Yeni #%order_id% numaralı sipariş oluşturuldu ve bakiyenizden %price% düşüldü.',
];
