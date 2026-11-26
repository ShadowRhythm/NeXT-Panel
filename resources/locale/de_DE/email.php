<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - Bestätigungs-E-Mail',
    'verify_email_title' => 'E-Mail-Bestätigung',
    'verify_code_text' => 'Ihr angeforderter E-Mail-Bestätigungscode lautet: %code%',
    'verify_code_expire' => 'Dieser Bestätigungscode ist gültig bis %expire%.',
    'verify_code_ignore' => 'Falls Sie diesen Code nicht angefordert haben, ignorieren Sie diese E-Mail bitte.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - Passwort zurücksetzen',
    'password_reset_title' => 'Passwort zurücksetzen',
    'password_reset_desc' => 'Sie erhalten diese E-Mail, weil Sie bei %app_name% das Zurücksetzen Ihres Passworts angefordert haben. Falls dies nicht von Ihnen veranlasst wurde, ignorieren Sie diese E-Mail bitte.',
    'click_to_reset_password' => 'Hier klicken, um Ihr Passwort zurückzusetzen',

    // New User (new_user.tpl)
    'account_created_title' => 'Konto erstellt',

    // Test Email (test.tpl)
    'test_email_subject' => 'Test-E-Mail',
    'test_email_title' => 'E-Mail-Zustelltest',
    'test_email_desc' => 'Dies ist eine Test-E-Mail. Wenn Sie diese Nachricht erhalten, funktioniert Ihre E-Mail-Konfiguration einwandfrei.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - Täglicher Traffic-Bericht & Ankündigungen',
    'traffic_report_title' => 'Täglicher Traffic-Bericht',
    'username_label' => 'Benutzername: %username%',
    'email_label' => 'E-Mail: %email%',
    'total_traffic' => 'Gesamt-Traffic: %traffic%',
    'used_traffic' => 'Verbrauchter Traffic: %traffic%',
    'remaining_traffic' => 'Verbleibender Traffic: %traffic%',
    'today_traffic' => 'Heutiger Traffic: %traffic%',
    'site_announcement' => 'Website-Ankündigung:',
    'good_night' => 'Gute Nacht!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'Täglicher Finanzbericht',
    'finance_weekly_title' => 'Wöchentlicher Finanzbericht',
    'finance_monthly_title' => 'Monatlicher Finanzbericht',
    'finance_last_week_orders' => 'Bestellungen letzte Woche gesamt: %count%',
    'finance_last_week_total' => 'Einnahmen letzte Woche gesamt: %amount%',
    'finance_last_month_orders' => 'Bestellungen letzten Monat gesamt: %count%',
    'finance_last_month_total' => 'Einnahmen letzten Monat gesamt: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'Systemhinweis',
    'modify_email_settings' => 'E-Mail-Einstellungen ändern',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - Ihre Kontostufe ist abgelaufen',
    'free_traffic_reset_subject' => '%app_name% - Hinweis zum Zurücksetzen des kostenlosen Traffics',
    'free_traffic_reset_body' => 'Hallo, Ihr kostenloser Traffic wurde auf %bandwidth% GB zurückgesetzt.',
    'low_traffic_subject' => '%app_name% - Ihr verbleibender Traffic ist gering',
    'low_traffic_body' => 'Hallo, das System hat festgestellt, dass Ihr verbleibender Traffic unter %limit% %unit% liegt.',
    'new_login_subject' => '%app_name% - Benachrichtigung über neue Anmeldung',
    'new_login_body' => 'Ihr Konto hat sich am %time% über die IP %ip% im Benutzerpanel angemeldet',
    'new_sub_subject' => '%app_name% - Benachrichtigung über neues Abonnement',
    'new_sub_body' => 'Ihr Konto hat am %time% über die IP %ip% ein Abonnement abgerufen',
    'ticket_replied_subject' => '%app_name% - Ticket beantwortet',
    'ticket_replied_body' => 'Hallo, jemand hat auf Ihr Ticket geantwortet. Bitte sehen Sie nach.',
    'new_ticket_subject' => '%app_name% - Neues Ticket erstellt',
    'new_ticket_body' => 'Admin, jemand hat ein neues Ticket eröffnet. Bitte bearbeiten Sie es zeitnah.',
    'ticket_replied_admin_body' => 'Admin, jemand hat auf Ticket <a href="%ticket_url%">#%ticket_id%</a> geantwortet. Bitte bearbeiten Sie es zeitnah.',
    'ticket_replied_user_body' => 'Hallo, jemand hat auf Ihr <a href="%ticket_url%">Ticket</a> geantwortet. Bitte sehen Sie nach.',
    'ticket_ai_replied_user_body' => 'Hallo, der KI-Assistent hat auf Ihr <a href="%ticket_url%">Ticket</a> geantwortet. Bitte sehen Sie nach.',
    'class_expired_body' => 'Hallo, das System hat festgestellt, dass Ihre Kontostufe abgelaufen ist.',
    'class_expired_reset_body' => 'Ihr Traffic wurde auf %bandwidth% GB zurückgesetzt.',
    'auto_renew_disabled_subject' => '%app_name% - Automatische Bestellverlängerung deaktiviert',
    'auto_renew_disabled_off_shelf_body' => 'Ihr Produkt [%product%] (Bestellung #%order_id%) wurde aus dem Sortiment genommen oder eingestellt, daher wurde die automatische Verlängerung deaktiviert. Bitte besuchen Sie den Shop, um ein anderes Paket zu wählen.',
    'auto_renew_failed_subject' => '%app_name% - Zahlung für automatische Verlängerung fehlgeschlagen',
    'auto_renew_failed_tabp_body' => 'Ihr Produkt [%product%] (Bestellung #%order_id%) läuft bald ab und die Abbuchung für die automatische Verlängerung ist fehlgeschlagen (erforderlich: %price%, aktuelles Guthaben: %balance%). Bitte laden Sie Ihr Guthaben rechtzeitig vor Ablauf auf, damit das System einen erneuten Versuch unternehmen kann; nach Ablauf erfolgen keine weiteren Versuche.',
    'auto_renew_failed_time_body' => 'Die Abbuchung für die automatische Verlängerung Ihres Produkts [%product%] (Bestellung #%order_id%) ist fehlgeschlagen (erforderlich: %price%, aktuelles Guthaben: %balance%). Bitte laden Sie Ihr Guthaben rechtzeitig auf, das System wird den Vorgang automatisch erneut versuchen.',
    'auto_renew_out_of_stock_subject' => '%app_name% - Automatische Verlängerung nicht vorrätig',
    'auto_renew_out_of_stock_body' => 'Ihr Produkt [%product%] (Bestellung #%order_id%) läuft bald ab und die automatische Verlängerung ist fehlgeschlagen, da das Produkt vorübergehend ausverkauft ist. Bitte schauen Sie später wieder vorbei oder wählen Sie ein anderes Paket im Shop.',
    'auto_renew_success_subject' => '%app_name% - Automatische Bestellverlängerung erfolgreich',
    'auto_renew_success_body' => 'Ihr Produkt [%product%] wurde automatisch verlängert. Die neue Bestellung #%order_id% wurde erstellt und %price% wurden von Ihrem Guthaben abgebucht.',
];
