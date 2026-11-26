<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => 'Im Benutzerzentrum anmelden',
    'register_title' => 'Konto registrieren',
    'forgot_password_title' => 'Passwort vergessen',
    'reset_password_title' => 'Neues Passwort festlegen',
    'mfa_title' => 'Zwei-Faktor-Authentifizierung',

    // Form labels and placeholders
    'email' => 'E-Mail',
    'email_placeholder' => 'E-Mail (Outlook, Gmail, iCloud empfohlen)',
    'registered_email' => 'Registrierte E-Mail-Adresse',
    'password' => 'Passwort',
    'password_placeholder' => 'Passwort',
    'confirm_password' => 'Passwort bestätigen',
    'confirm_password_placeholder' => 'Passwort wiederholen',
    'new_password' => 'Neues Passwort',
    'new_password_placeholder' => 'Bitte neues Passwort eingeben',
    'confirm_new_password' => 'Neues Passwort bestätigen',
    'confirm_new_password_placeholder' => 'Bitte neues Passwort erneut eingeben',
    'nickname' => 'Benutzername',
    'invite_code_optional' => 'Einladungscode (optional)',
    'invite_code_required' => 'Einladungscode (erforderlich)',
    'email_verify_code' => 'E-Mail-Bestätigungscode',
    'email_verify_code_placeholder' => 'Bestätigungscode',

    // Buttons
    'btn_login' => 'Anmelden',
    'btn_webauthn_login' => 'Mit WebAuthn anmelden',
    'btn_register' => 'Neues Konto registrieren',
    'btn_get_code' => 'Code anfordern',
    'btn_send_reset_email' => 'E-Mail senden',
    'btn_reset_password' => 'Zurücksetzen',
    'btn_submit' => 'Absenden',
    'btn_fido_verify' => 'Mit FIDO2 verifizieren',

    // Checkboxes and links
    'remember_me' => 'Dieses Gerät merken',
    'forgot_password' => 'Passwort vergessen?',
    'no_account' => 'Noch kein Konto?',
    'click_to_register' => 'Registrieren',
    'already_have_account' => 'Sie haben bereits ein Konto?',
    'click_to_login' => 'Anmelden',
    'agree_terms_prefix' => 'Ich habe die folgende Vereinbarung gelesen und akzeptiere sie:',
    'terms_and_privacy' => 'Nutzungsbedingungen und Datenschutzerklärung',

    // Hints and notices
    'login_upgrade_notice' => 'Das Panel wurde aktualisiert. Falls Ihr Passwort nicht akzeptiert wird, setzen Sie es bitte vor der Anmeldung zurück!',
    'reset_password_desc' => 'Wir senden eine E-Mail mit einem Link zum Zurücksetzen Ihres Passworts an Ihre registrierte E-Mail-Adresse.',
    'verify_code_tip' => 'Keinen Code erhalten? Bitte verwenden Sie einen empfohlenen E-Mail-Anbieter (Outlook, Gmail, iCloud, ProtonMail usw.) oder prüfen Sie Ihren Spam-Ordner und fügen Sie uns zur Whitelist hinzu (in den Posteingang verschieben)!',
    'reset_email_tip' => 'Falls Sie keine E-Mail erhalten haben, prüfen Sie bitte Ihren Spam-Ordner und fügen Sie unsere E-Mails zur Whitelist hinzu (in den Posteingang verschieben)!',
    'registration_closed' => 'Die Registrierung ist derzeit geschlossen. Bitte schauen Sie in ein paar Tagen wieder vorbei.',
    'mfa_notice' => 'Die Zwei-Faktor-Authentifizierung ist für Ihr Konto aktiviert. Bitte schließen Sie zu Ihrer Sicherheit die zusätzliche Identitätsprüfung ab.',
    'oauth_request_failed' => 'OAuth-Anfrage fehlgeschlagen',
    'slack_already_bound' => 'Slack-Konto ist bereits verknüpft',
    'discord_already_bound' => 'Discord-Konto ist bereits verknüpft',
    'please_login_first' => 'Bitte melden Sie sich zuerst an',
    'telegram_already_bound_unbind_first' => 'Telegram ist bereits verknüpft. Bitte heben Sie zuerst die bestehende Verknüpfung auf, wenn Sie das Konto wechseln möchten',
    'telegram_already_bound' => 'Telegram-Konto ist bereits verknüpft',
    'bind_success' => 'Erfolgreich verknüpft',
];
