<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - Email Verifikasi',
    'verify_email_title' => 'Verifikasi Email',
    'verify_code_text' => 'Kode verifikasi email yang Anda minta adalah: %code%',
    'verify_code_expire' => 'Kode verifikasi ini berlaku hingga %expire%.',
    'verify_code_ignore' => 'Jika Anda tidak meminta kode ini, silakan abaikan email ini.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - Reset Kata Sandi',
    'password_reset_title' => 'Reset Kata Sandi',
    'password_reset_desc' => 'Anda menerima email ini karena Anda meminta pengaturan ulang kata sandi di %app_name%. Jika bukan Anda yang meminta ini, silakan abaikan email ini.',
    'click_to_reset_password' => 'Klik di sini untuk mengatur ulang kata sandi Anda',

    // New User (new_user.tpl)
    'account_created_title' => 'Akun Dibuat',

    // Test Email (test.tpl)
    'test_email_subject' => 'Email Uji Coba',
    'test_email_title' => 'Uji Pengiriman Email',
    'test_email_desc' => 'Ini adalah email uji coba. Jika Anda menerimanya, konfigurasi email Anda berfungsi dengan baik.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - Laporan Trafik Harian & Pengumuman',
    'traffic_report_title' => 'Laporan Trafik Harian',
    'username_label' => 'Nama Pengguna: %username%',
    'email_label' => 'Email: %email%',
    'total_traffic' => 'Total Trafik: %traffic%',
    'used_traffic' => 'Trafik Digunakan: %traffic%',
    'remaining_traffic' => 'Sisa Trafik: %traffic%',
    'today_traffic' => 'Trafik Hari Ini: %traffic%',
    'site_announcement' => 'Pengumuman Situs:',
    'good_night' => 'Selamat beristirahat!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'Laporan Keuangan Harian',
    'finance_weekly_title' => 'Laporan Keuangan Mingguan',
    'finance_monthly_title' => 'Laporan Keuangan Bulanan',
    'finance_last_week_orders' => 'Total pesanan minggu lalu: %count%',
    'finance_last_week_total' => 'Total pendapatan minggu lalu: %amount%',
    'finance_last_month_orders' => 'Total pesanan bulan lalu: %count%',
    'finance_last_month_total' => 'Total pendapatan bulan lalu: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'Pemberitahuan Sistem',
    'modify_email_settings' => 'Ubah Pengaturan Email',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - Level akun Anda telah kedaluwarsa',
    'free_traffic_reset_subject' => '%app_name% - Pemberitahuan Reset Trafik Gratis',
    'free_traffic_reset_body' => 'Halo, trafik gratis Anda telah direset menjadi %bandwidth% GB.',
    'low_traffic_subject' => '%app_name% - Sisa trafik Anda menipis',
    'low_traffic_body' => 'Halo, sistem mendeteksi bahwa sisa trafik Anda lebih rendah dari %limit% %unit%.',
    'new_login_subject' => '%app_name% - Pemberitahuan Login Baru',
    'new_login_body' => 'Akun Anda masuk ke panel pengguna dari IP %ip% pada %time%',
    'new_sub_subject' => '%app_name% - Pemberitahuan Langganan Baru',
    'new_sub_body' => 'Akun Anda berlangganan dari IP %ip% pada %time%',
    'ticket_replied_subject' => '%app_name% - Tiket Dibalas',
    'ticket_replied_body' => 'Halo, seseorang membalas tiket Anda, silakan periksa.',
    'new_ticket_subject' => '%app_name% - Tiket Baru Dibuat',
    'new_ticket_body' => 'Admin, seseorang membuka tiket baru, harap segera ditangani.',
    'ticket_replied_admin_body' => 'Admin, seseorang membalas tiket <a href="%ticket_url%">#%ticket_id%</a>, harap segera ditangani.',
    'ticket_replied_user_body' => 'Halo, seseorang membalas <a href="%ticket_url%">tiket</a> Anda, silakan periksa.',
    'ticket_ai_replied_user_body' => 'Halo, Asisten AI membalas <a href="%ticket_url%">tiket</a> Anda, silakan periksa.',
    'class_expired_body' => 'Halo, sistem mendeteksi bahwa level akun Anda telah kedaluwarsa.',
    'class_expired_reset_body' => 'Trafik Anda telah direset menjadi %bandwidth% GB.',
    'auto_renew_disabled_subject' => '%app_name% - Perpanjangan Otomatis Pesanan Dinonaktifkan',
    'auto_renew_disabled_off_shelf_body' => 'Produk Anda [%product%] (Pesanan #%order_id%) telah ditarik atau dihentikan, sehingga perpanjangan otomatis dinonaktifkan secara otomatis. Silakan kunjungi toko untuk memilih paket lain.',
    'auto_renew_failed_subject' => '%app_name% - Pembayaran Perpanjangan Otomatis Pesanan Gagal',
    'auto_renew_failed_tabp_body' => 'Produk Anda [%product%] (Pesanan #%order_id%) akan segera kedaluwarsa, dan pemotongan saldo untuk perpanjangan otomatis gagal (membutuhkan %price%, saldo saat ini %balance%). Harap isi saldo tepat waktu sebelum kedaluwarsa agar sistem dapat mencoba ulang; tidak ada percobaan ulang setelah kedaluwarsa.',
    'auto_renew_failed_time_body' => 'Pemotongan saldo perpanjangan otomatis untuk produk Anda [%product%] (Pesanan #%order_id%) gagal (membutuhkan %price%, saldo saat ini %balance%). Harap isi saldo tepat waktu dan sistem akan mencoba ulang secara otomatis.',
    'auto_renew_out_of_stock_subject' => '%app_name% - Stok Perpanjangan Otomatis Pesanan Habis',
    'auto_renew_out_of_stock_body' => 'Produk Anda [%product%] (Pesanan #%order_id%) akan segera kedaluwarsa, dan perpanjangan otomatis gagal karena stok produk sedang habis sementara. Silakan periksa kembali atau pilih paket lain di toko.',
    'auto_renew_success_subject' => '%app_name% - Perpanjangan Otomatis Pesanan Berhasil',
    'auto_renew_success_body' => 'Produk Anda [%product%] telah diperpanjang secara otomatis. Pesanan baru #%order_id% telah dibuat dan %price% telah dipotong dari saldo Anda.',
];
