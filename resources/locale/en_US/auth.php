<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => 'Sign in to User Center',
    'register_title' => 'Register Account',
    'forgot_password_title' => 'Forgot Password',
    'reset_password_title' => 'Set New Password',
    'mfa_title' => 'Two-Factor Authentication',

    // Form labels and placeholders
    'email' => 'Email',
    'email_placeholder' => 'Email (Outlook, Gmail, iCloud recommended)',
    'registered_email' => 'Registered Email',
    'password' => 'Password',
    'password_placeholder' => 'Password',
    'confirm_password' => 'Confirm Password',
    'confirm_password_placeholder' => 'Repeat password',
    'new_password' => 'New Password',
    'new_password_placeholder' => 'Please enter new password',
    'confirm_new_password' => 'Re-enter New Password',
    'confirm_new_password_placeholder' => 'Please re-enter new password',
    'nickname' => 'Nickname',
    'invite_code_optional' => 'Invitation Code (Optional)',
    'invite_code_required' => 'Invitation Code (Required)',
    'email_verify_code' => 'Email Verification Code',
    'email_verify_code_placeholder' => 'Verification code',

    // Buttons
    'btn_login' => 'Sign in',
    'btn_webauthn_login' => 'Sign in with WebAuthn',
    'btn_register' => 'Register New Account',
    'btn_get_code' => 'Get Code',
    'btn_send_reset_email' => 'Send Email',
    'btn_reset_password' => 'Reset',
    'btn_submit' => 'Submit',
    'btn_fido_verify' => 'Verify with FIDO2',

    // Checkboxes and links
    'remember_me' => 'Remember this device',
    'forgot_password' => 'Forgot password',
    'no_account' => 'Don\'t have an account yet?',
    'click_to_register' => 'Sign up',
    'already_have_account' => 'Already have an account?',
    'click_to_login' => 'Sign in',
    'agree_terms_prefix' => 'I have read and agree to the',
    'terms_and_privacy' => 'Terms of Service and Privacy Policy',

    // Hints and notices
    'login_upgrade_notice' => 'The panel has been upgraded. If password is incorrect, please reset password before logging in!',
    'reset_password_desc' => 'We will send an email to your registered email address containing a link to reset your password.',
    'verify_code_tip' => 'Didn\'t receive the code? Please change to a recommended email provider (Outlook, Gmail, iCloud, Protonmail, etc.) or check your spam folder and whitelist our emails (move to inbox)!',
    'reset_email_tip' => 'If you haven\'t received the email, please check your spam folder and whitelist our emails (move to inbox)!',
    'registration_closed' => 'Registration is currently closed. Please check back in a few days.',
    'mfa_notice' => 'Two-factor authentication is enabled for your account. For your security, please complete additional identity verification.',
    'oauth_request_failed' => 'OAuth request failed',
    'slack_already_bound' => 'Slack account is already linked',
    'discord_already_bound' => 'Discord account is already linked',
    'please_login_first' => 'Please sign in first',
    'telegram_already_bound_unbind_first' => 'Telegram is already linked. Please unbind first if you want to switch accounts',
    'telegram_already_bound' => 'Telegram account is already linked',
    'bind_success' => 'Linked successfully',
    'or_continue_with' => 'Or continue with',
    'oauth_disabled' => 'This OAuth provider is disabled or not configured',
    'oauth_email_unverified' => 'Your OAuth email is not verified and cannot be used to sign in or register',
    'oauth_already_bound' => 'This OAuth account is already linked to another user',
    'oauth_invite_title' => 'Enter Invitation Code',
    'oauth_invite_desc' => 'Verified email %email%. Enter an invitation code to finish creating your account.',
    'btn_complete_registration' => 'Complete Registration',
    'oauth_accounts_title' => 'Social Login Accounts',
    'oauth_accounts_desc' => 'Link your Google or GitHub account for one-click sign in.',
    'oauth_bound' => 'Linked',
    'oauth_not_bound' => 'Not linked',
    'oauth_bind' => 'Link Account',
];
