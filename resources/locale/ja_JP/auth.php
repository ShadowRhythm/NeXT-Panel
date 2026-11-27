<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => 'ユーザーセンターにログイン',
    'register_title' => '新規アカウント登録',
    'forgot_password_title' => 'パスワードをお忘れの方',
    'reset_password_title' => '新しいパスワードの設定',
    'mfa_title' => '2段階認証',

    // Form labels and placeholders
    'email' => 'メールアドレス',
    'email_placeholder' => 'メールアドレス（Outlook、Gmail、iCloudなどを推奨）',
    'registered_email' => '登録メールアドレス',
    'password' => 'パスワード',
    'password_placeholder' => 'パスワード',
    'confirm_password' => 'パスワード（確認）',
    'confirm_password_placeholder' => 'パスワードを再入力',
    'new_password' => '新しいパスワード',
    'new_password_placeholder' => '新しいパスワードを入力してください',
    'confirm_new_password' => '新しいパスワード（確認）',
    'confirm_new_password_placeholder' => '新しいパスワードを再入力してください',
    'nickname' => 'ニックネーム',
    'invite_code_optional' => '招待コード（任意）',
    'invite_code_required' => '招待コード（必須）',
    'email_verify_code' => 'メール認証コード',
    'email_verify_code_placeholder' => '認証コード',

    // Buttons
    'btn_login' => 'ログイン',
    'btn_webauthn_login' => 'WebAuthn でログイン',
    'btn_register' => '新規アカウント登録',
    'btn_get_code' => 'コードを取得',
    'btn_send_reset_email' => 'メールを送信',
    'btn_reset_password' => 'リセット',
    'btn_submit' => '送信',
    'btn_fido_verify' => 'FIDO2 で認証',

    // Checkboxes and links
    'remember_me' => 'このデバイスを記憶する',
    'forgot_password' => 'パスワードをお忘れですか？',
    'no_account' => 'アカウントをお持ちではありませんか？',
    'click_to_register' => '新規登録はこちら',
    'already_have_account' => 'すでにアカウントをお持ちですか？',
    'click_to_login' => 'ログインはこちら',
    'agree_terms_prefix' => '次の内容に同意します:',
    'terms_and_privacy' => '利用規約およびプライバシーポリシー',

    // Hints and notices
    'login_upgrade_notice' => 'システムがアップグレードされました。パスワードが正しくないと表示される場合は、パスワードをリセットしてからログインしてください！',
    'reset_password_desc' => '登録されたメールアドレスに、パスワード再設定用リンクを記載したメールを送信します。',
    'verify_code_tip' => '認証コードが届かない場合：推奨メールプロバイダー（Outlook、Gmail、iCloud、ProtonMailなど）に変更するか、迷惑メールフォルダを確認して当サービスからのメールを受信トレイに移動（ホワイトリスト登録）してください！',
    'reset_email_tip' => 'メールが届かない場合は、迷惑メールフォルダを確認して当サービスからのメールを受信トレイに移動（ホワイトリスト登録）してください！',
    'registration_closed' => '現在は新規登録を受け付けていません。後日改めてご確認ください。',
    'mfa_notice' => 'アカウントの2段階認証が有効になっています。セキュリティ保護のため、追加の本人確認を完了してください。',
    'oauth_request_failed' => 'OAuthリクエストが失敗しました',
    'slack_already_bound' => 'Slackアカウントは既に連携されています',
    'discord_already_bound' => 'Discordアカウントは既に連携されています',
    'please_login_first' => '先にログインしてください',
    'telegram_already_bound_unbind_first' => '既にTelegramと連携されています。変更する場合は先に解除してください',
    'telegram_already_bound' => 'Telegramアカウントは既に連携されています',
    'bind_success' => '連携に成功しました',
    'or_continue_with' => 'または外部アカウントで続行',
    'oauth_disabled' => 'この OAuth ログインは無効か未設定です',
    'oauth_email_unverified' => '外部アカウントのメールが未確認のため利用できません',
    'oauth_already_bound' => 'この外部アカウントは既に他のユーザーに連携されています',
    'oauth_invite_title' => '招待コードを入力して登録を完了',
    'oauth_invite_desc' => '確認済みメール %email%。アカウント作成を完了するには招待コードを入力してください。',
    'btn_complete_registration' => '登録を完了してログイン',
    'oauth_accounts_title' => 'ソーシャルログイン連携',
    'oauth_accounts_desc' => 'Google または GitHub を連携するとワンクリックでログインできます。',
    'oauth_bound' => '連携済み',
    'oauth_not_bound' => '未連携',
    'oauth_bind' => 'アカウント連携',
];
