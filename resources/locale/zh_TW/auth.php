<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => '登入到使用者中心',
    'register_title' => '註冊帳號',
    'forgot_password_title' => '忘記密碼',
    'reset_password_title' => '設定新密碼',
    'mfa_title' => '雙重驗證',

    // Form labels and placeholders
    'email' => '電子信箱',
    'email_placeholder' => '電子信箱 推薦Outlook、Gmail、iCloud等',
    'registered_email' => '註冊信箱',
    'password' => '登入密碼',
    'password_placeholder' => '登入密碼',
    'confirm_password' => '重複登入密碼',
    'confirm_password_placeholder' => '重複登入密碼',
    'new_password' => '新密碼',
    'new_password_placeholder' => '請輸入新密碼',
    'confirm_new_password' => '再次輸入新密碼',
    'confirm_new_password_placeholder' => '請再次輸入新密碼',
    'nickname' => '暱稱',
    'invite_code_optional' => '註冊邀請碼（選填）',
    'invite_code_required' => '註冊邀請碼（必填）',
    'email_verify_code' => '信箱驗證碼',
    'email_verify_code_placeholder' => '信箱驗證碼',

    // Buttons
    'btn_login' => '登入',
    'btn_webauthn_login' => '使用 WebAuthn 登入',
    'btn_register' => '註冊新帳號',
    'btn_get_code' => '取得',
    'btn_send_reset_email' => '傳送郵件',
    'btn_reset_password' => '重設',
    'btn_submit' => '提交',
    'btn_fido_verify' => '使用 FIDO2 驗證',

    // Checkboxes and links
    'remember_me' => '記住此裝置',
    'forgot_password' => '忘記密碼',
    'no_account' => '還沒有帳號？',
    'click_to_register' => '點擊註冊',
    'already_have_account' => '已有帳號？',
    'click_to_login' => '點擊登入',
    'agree_terms_prefix' => '我已閱讀並同意',
    'terms_and_privacy' => '服務條款與隱私政策',

    // Hints and notices
    'login_upgrade_notice' => '面板已升級，若提示密碼不正確請重設密碼後登入！',
    'reset_password_desc' => '我們將向你的註冊信箱傳送一封郵件，內容包含重設密碼的連結。',
    'verify_code_tip' => '收不到驗證碼？請更換推薦信箱（Outlook、Gmail、iCloud、Protonmail等）或檢查垃圾信件匣並將我們的郵件加入白名單（移至收件匣）！',
    'reset_email_tip' => '若沒收到郵件，請檢查垃圾信件匣並將我們的郵件加入白名單（移至收件匣）！',
    'registration_closed' => '尚未開放註冊，過兩天再來看看吧',
    'mfa_notice' => '您的帳戶已啟用雙重驗證，為了您的帳戶安全，請完成附加身分驗證。',
    'oauth_request_failed' => 'OAuth 請求失敗',
    'slack_already_bound' => 'Slack 帳戶已綁定',
    'discord_already_bound' => 'Discord 帳戶已綁定',
    'please_login_first' => '請先登入',
    'telegram_already_bound_unbind_first' => '你已綁定 Telegram，如需換綁請先解綁',
    'telegram_already_bound' => 'Telegram 帳戶已綁定',
    'bind_success' => '綁定成功',
    'or_continue_with' => '或使用第三方帳號登入',
    'oauth_disabled' => '該第三方登入方式未啟用或未設定',
    'oauth_email_unverified' => '第三方帳號信箱未驗證，無法登入或註冊',
    'oauth_already_bound' => '該第三方帳號已被其他使用者綁定',
    'oauth_invite_title' => '填寫邀請碼完成註冊',
    'oauth_invite_desc' => '已驗證第三方信箱 %email%，請輸入邀請碼以完成帳號建立。',
    'btn_complete_registration' => '完成註冊並登入',
    'oauth_accounts_title' => '第三方帳號登入綁定',
    'oauth_accounts_desc' => '綁定 Google 或 GitHub 後，可直接使用對應帳號一鍵登入面板。',
    'oauth_bound' => '已綁定',
    'oauth_not_bound' => '未綁定',
    'oauth_bind' => '綁定帳號',
];
