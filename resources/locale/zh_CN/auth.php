<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => '登录到用户中心',
    'register_title' => '注册账户',
    'forgot_password_title' => '忘记密码',
    'reset_password_title' => '设置新密码',
    'mfa_title' => '二步验证',

    // Form labels and placeholders
    'email' => '邮箱',
    'email_placeholder' => '电子邮箱 推荐Outlook、Gmail、Icloud等',
    'registered_email' => '注册邮箱',
    'password' => '登录密码',
    'password_placeholder' => '登录密码',
    'confirm_password' => '重复登录密码',
    'confirm_password_placeholder' => '重复登录密码',
    'new_password' => '新密码',
    'new_password_placeholder' => '请输入新密码',
    'confirm_new_password' => '再次输入新密码',
    'confirm_new_password_placeholder' => '请再次输入新密码',
    'nickname' => '昵称',
    'invite_code_optional' => '注册邀请码（可选）',
    'invite_code_required' => '注册邀请码（必填）',
    'email_verify_code' => '邮箱验证码',
    'email_verify_code_placeholder' => '邮箱验证码',

    // Buttons
    'btn_login' => '登录',
    'btn_webauthn_login' => '使用WebAuthn登录',
    'btn_register' => '注册新账户',
    'btn_get_code' => '获取',
    'btn_send_reset_email' => '发送邮件',
    'btn_reset_password' => '重置',
    'btn_submit' => '提交',
    'btn_fido_verify' => '使用 FIDO2 验证',

    // Checkboxes and links
    'remember_me' => '记住此设备',
    'forgot_password' => '忘记密码',
    'no_account' => '还没有账户？',
    'click_to_register' => '点击注册',
    'already_have_account' => '已有账户？',
    'click_to_login' => '点击登录',
    'agree_terms_prefix' => '我已阅读并同意',
    'terms_and_privacy' => '服务条款与隐私政策',

    // Hints and notices
    'login_upgrade_notice' => '面板已升级，若提示密码不正确请重置密码后登录！',
    'reset_password_desc' => '我们将向你的注册邮箱发送一封邮件，邮件内容中包含一个可以重设密码的链接。',
    'verify_code_tip' => '收不到验证码？请更换推荐邮箱（Outlook、Gmail、Icloud、Protonmail等）或检查邮件“垃圾箱”并将我们的邮件设置为白名单（移回收件箱）！',
    'reset_email_tip' => '若没收到邮件，请检查邮件‘垃圾箱”并将我们的邮件设置为白名单（移回收件箱）！',
    'registration_closed' => '还没有开放注册，过两天再来看看吧',
    'mfa_notice' => '您的账户已启用二步验证，为了您的账户安全，请您完成附加身份验证。',
    'oauth_request_failed' => 'OAuth 请求失败',
    'slack_already_bound' => 'Slack 账户已绑定',
    'discord_already_bound' => 'Discord 账户已绑定',
    'please_login_first' => '请先登录',
    'telegram_already_bound_unbind_first' => '你已绑定 Telegram，如需换绑请先解绑',
    'telegram_already_bound' => 'Telegram 账户已绑定',
    'bind_success' => '绑定成功',
    'or_continue_with' => '或使用第三方账号登录',
    'oauth_disabled' => '该第三方登录方式未启用或未配置',
    'oauth_email_unverified' => '第三方账号邮箱未验证，无法登录或注册',
    'oauth_already_bound' => '该第三方账号已被其他用户绑定',
    'oauth_invite_title' => '填写邀请码完成注册',
    'oauth_invite_desc' => '已验证第三方邮箱 %email%，请输入邀请码以完成账号创建。',
    'btn_complete_registration' => '完成注册并登录',
    'oauth_accounts_title' => '第三方账号登录绑定',
    'oauth_accounts_desc' => '绑定 Google 或 GitHub 后，可直接使用对应账号一键登录面板。',
    'oauth_bound' => '已绑定',
    'oauth_not_bound' => '未绑定',
    'oauth_bind' => '绑定账号',
];
