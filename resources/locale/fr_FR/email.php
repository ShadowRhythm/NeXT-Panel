<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - E-mail de vérification',
    'verify_email_title' => 'Vérification de l\'adresse e-mail',
    'verify_code_text' => 'Votre code de vérification d\'e-mail est : %code%',
    'verify_code_expire' => 'Ce code de vérification est valide jusqu\'au %expire%.',
    'verify_code_ignore' => 'Si vous n\'avez pas demandé ce code, veuillez ignorer cet e-mail.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - Réinitialisation du mot de passe',
    'password_reset_title' => 'Réinitialisation du mot de passe',
    'password_reset_desc' => 'Vous recevez cet e-mail car vous avez demandé la réinitialisation de votre mot de passe sur %app_name%. Si vous n\'êtes pas à l\'origine de cette demande, veuillez ignorer cet e-mail.',
    'click_to_reset_password' => 'Cliquez ici pour réinitialiser votre mot de passe',

    // New User (new_user.tpl)
    'account_created_title' => 'Compte créé',

    // Test Email (test.tpl)
    'test_email_subject' => 'E-mail de test',
    'test_email_title' => 'Test de distribution des e-mails',
    'test_email_desc' => 'Ceci est un e-mail de test. Si vous recevez ce message, votre configuration d\'e-mail fonctionne correctement.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - Rapport de trafic quotidien et annonces',
    'traffic_report_title' => 'Rapport de trafic quotidien',
    'username_label' => 'Nom d\'utilisateur : %username%',
    'email_label' => 'E-mail : %email%',
    'total_traffic' => 'Trafic total : %traffic%',
    'used_traffic' => 'Trafic utilisé : %traffic%',
    'remaining_traffic' => 'Trafic restant : %traffic%',
    'today_traffic' => 'Trafic du jour : %traffic%',
    'site_announcement' => 'Annonce du site :',
    'good_night' => 'Bonne nuit !',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'Rapport financier quotidien',
    'finance_weekly_title' => 'Rapport financier hebdomadaire',
    'finance_monthly_title' => 'Rapport financier mensuel',
    'finance_last_week_orders' => 'Total des commandes de la semaine dernière : %count%',
    'finance_last_week_total' => 'Revenu total de la semaine dernière : %amount%',
    'finance_last_month_orders' => 'Total des commandes du mois dernier : %count%',
    'finance_last_month_total' => 'Revenu total du mois dernier : %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'Notification système',
    'modify_email_settings' => 'Modifier les paramètres d\'e-mail',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - Le niveau de votre compte a expiré',
    'free_traffic_reset_subject' => '%app_name% - Avis de réinitialisation du trafic gratuit',
    'free_traffic_reset_body' => 'Bonjour, votre trafic gratuit a été réinitialisé à %bandwidth% Go.',
    'low_traffic_subject' => '%app_name% - Votre trafic restant est faible',
    'low_traffic_body' => 'Bonjour, le système a détecté que votre trafic restant est inférieur à %limit% %unit%.',
    'new_login_subject' => '%app_name% - Notification de nouvelle connexion',
    'new_login_body' => 'Votre compte s\'est connecté au panneau utilisateur depuis l\'adresse IP %ip% à %time%',
    'new_sub_subject' => '%app_name% - Notification de nouvel abonnement',
    'new_sub_body' => 'Votre compte a accédé à l\'abonnement depuis l\'adresse IP %ip% à %time%',
    'ticket_replied_subject' => '%app_name% - Réponse à votre ticket',
    'ticket_replied_body' => 'Bonjour, une réponse a été apportée à votre ticket, veuillez la consulter.',
    'new_ticket_subject' => '%app_name% - Nouveau ticket créé',
    'new_ticket_body' => 'Administrateur, un nouveau ticket a été ouvert, veuillez le traiter dès que possible.',
    'ticket_replied_admin_body' => 'Administrateur, quelqu\'un a répondu au ticket <a href="%ticket_url%">#%ticket_id%</a>, veuillez le traiter à temps.',
    'ticket_replied_user_body' => 'Bonjour, quelqu\'un a répondu à votre <a href="%ticket_url%">ticket</a>, veuillez le consulter.',
    'ticket_ai_replied_user_body' => 'Bonjour, l\'assistant IA a répondu à votre <a href="%ticket_url%">ticket</a>, veuillez le consulter.',
    'class_expired_body' => 'Bonjour, le système a détecté que le niveau de votre compte a expiré.',
    'class_expired_reset_body' => 'Votre trafic a été réinitialisé à %bandwidth% Go.',
    'auto_renew_disabled_subject' => '%app_name% - Renouvellement automatique désactivé',
    'auto_renew_disabled_off_shelf_body' => 'Votre produit [%product%] (Commande #%order_id%) a été retiré de la vente ; le renouvellement automatique a donc été désactivé. Veuillez choisir un autre forfait dans la boutique.',
    'auto_renew_failed_subject' => '%app_name% - Échec du paiement du renouvellement automatique',
    'auto_renew_failed_tabp_body' => 'Votre produit [%product%] (Commande #%order_id%) arrive à expiration et le débit automatique a échoué (requis : %price%, solde actuel : %balance%). Veuillez recharger avant expiration pour qu\'une nouvelle tentative ait lieu.',
    'auto_renew_failed_time_body' => 'Le débit automatique pour [%product%] (Commande #%order_id%) a échoué (requis : %price%, solde actuel : %balance%). Veuillez recharger votre solde pour relancer automatiquement.',
    'auto_renew_out_of_stock_subject' => '%app_name% - Rupture de stock pour le renouvellement automatique',
    'auto_renew_out_of_stock_body' => 'Votre produit [%product%] (Commande #%order_id%) arrive à expiration, mais le renouvellement automatique a échoué en raison d\'une rupture de stock. Veuillez choisir un autre forfait dans la boutique.',
    'auto_renew_success_subject' => '%app_name% - Renouvellement automatique réussi',
    'auto_renew_success_body' => 'Votre produit [%product%] a été renouvelé automatiquement. La commande #%order_id% a été créée et %price% a été déduit de votre solde.',
];
