<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => 'Connexion à l\'espace utilisateur',
    'register_title' => 'Créer un compte',
    'forgot_password_title' => 'Mot de passe oublié',
    'reset_password_title' => 'Définir un nouveau mot de passe',
    'mfa_title' => 'Authentification à deux facteurs',

    // Form labels and placeholders
    'email' => 'E-mail',
    'email_placeholder' => 'E-mail (Outlook, Gmail, iCloud recommandés)',
    'registered_email' => 'E-mail enregistré',
    'password' => 'Mot de passe',
    'password_placeholder' => 'Mot de passe',
    'confirm_password' => 'Confirmer le mot de passe',
    'confirm_password_placeholder' => 'Répétez le mot de passe',
    'new_password' => 'Nouveau mot de passe',
    'new_password_placeholder' => 'Veuillez saisir le nouveau mot de passe',
    'confirm_new_password' => 'Confirmer le nouveau mot de passe',
    'confirm_new_password_placeholder' => 'Veuillez confirmer le nouveau mot de passe',
    'nickname' => 'Pseudonyme',
    'invite_code_optional' => 'Code d\'invitation (facultatif)',
    'invite_code_required' => 'Code d\'invitation (obligatoire)',
    'email_verify_code' => 'Code de vérification par e-mail',
    'email_verify_code_placeholder' => 'Code de vérification',

    // Buttons
    'btn_login' => 'Se connecter',
    'btn_webauthn_login' => 'Se connecter avec WebAuthn',
    'btn_register' => 'Créer un compte',
    'btn_get_code' => 'Obtenir le code',
    'btn_send_reset_email' => 'Envoyer l\'e-mail',
    'btn_reset_password' => 'Réinitialiser',
    'btn_submit' => 'Soumettre',
    'btn_fido_verify' => 'Vérifier avec FIDO2',

    // Checkboxes and links
    'remember_me' => 'Se souvenir de cet appareil',
    'forgot_password' => 'Mot de passe oublié',
    'no_account' => 'Vous n\'avez pas encore de compte ?',
    'click_to_register' => 'S\'inscrire',
    'already_have_account' => 'Vous avez déjà un compte ?',
    'click_to_login' => 'Se connecter',
    'agree_terms_prefix' => 'J\'ai lu et j\'accepte les',
    'terms_and_privacy' => 'Conditions d\'utilisation et la Politique de confidentialité',

    // Hints and notices
    'login_upgrade_notice' => 'Le panneau a été mis à niveau. Si le mot de passe est incorrect, veuillez le réinitialiser avant de vous connecter !',
    'reset_password_desc' => 'Nous enverrons un e-mail à votre adresse enregistrée contenant un lien pour réinitialiser votre mot de passe.',
    'verify_code_tip' => 'Vous n\'avez pas reçu le code ? Veuillez utiliser un fournisseur d\'e-mail recommandé (Outlook, Gmail, iCloud, Protonmail, etc.) ou vérifier votre dossier spam et ajouter nos e-mails à la liste blanche (déplacer vers la boîte de réception) !',
    'reset_email_tip' => 'Si vous n\'avez pas reçu l\'e-mail, veuillez vérifier votre dossier spam et ajouter nos e-mails à la liste blanche (déplacer vers la boîte de réception) !',
    'registration_closed' => 'Les inscriptions sont actuellement fermées. Veuillez revenir dans quelques jours.',
    'mfa_notice' => 'L\'authentification à deux facteurs est activée pour votre compte. Pour votre sécurité, veuillez effectuer une vérification d\'identité supplémentaire.',
    'oauth_request_failed' => 'La requête OAuth a échoué',
    'slack_already_bound' => 'Le compte Slack est déjà lié',
    'discord_already_bound' => 'Le compte Discord est déjà lié',
    'please_login_first' => 'Veuillez d\'abord vous connecter',
    'telegram_already_bound_unbind_first' => 'Vous avez déjà lié Telegram. Veuillez d\'abord le délier pour changer de compte',
    'telegram_already_bound' => 'Le compte Telegram est déjà lié',
    'bind_success' => 'Lié avec succès',
    'or_continue_with' => 'Ou continuer avec',
    'oauth_disabled' => 'Ce fournisseur OAuth est désactivé ou non configuré',
    'oauth_email_unverified' => 'Votre e-mail OAuth n’est pas vérifié',
    'oauth_already_bound' => 'Ce compte OAuth est déjà lié à un autre utilisateur',
    'oauth_invite_title' => 'Saisir un code d’invitation',
    'oauth_invite_desc' => 'E-mail vérifié %email%. Saisissez un code d’invitation pour terminer la création de votre compte.',
    'btn_complete_registration' => 'Terminer l’inscription',
    'oauth_accounts_title' => 'Comptes de connexion sociale',
    'oauth_accounts_desc' => 'Liez votre compte Google ou GitHub pour vous connecter en un clic.',
    'oauth_bound' => 'Lié',
    'oauth_not_bound' => 'Non lié',
    'oauth_bind' => 'Lier le compte',
];
