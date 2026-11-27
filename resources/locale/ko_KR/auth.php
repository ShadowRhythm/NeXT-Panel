<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => '사용자 센터 로그인',
    'register_title' => '회원가입',
    'forgot_password_title' => '비밀번호 찾기',
    'reset_password_title' => '새 비밀번호 설정',
    'mfa_title' => '2단계 인증',

    // Form labels and placeholders
    'email' => '이메일',
    'email_placeholder' => '이메일 (Outlook, Gmail, iCloud 권장)',
    'registered_email' => '가입된 이메일',
    'password' => '비밀번호',
    'password_placeholder' => '비밀번호',
    'confirm_password' => '비밀번호 확인',
    'confirm_password_placeholder' => '비밀번호를 다시 입력하세요',
    'new_password' => '새 비밀번호',
    'new_password_placeholder' => '새 비밀번호를 입력하세요',
    'confirm_new_password' => '새 비밀번호 확인',
    'confirm_new_password_placeholder' => '새 비밀번호를 다시 입력하세요',
    'nickname' => '닉네임',
    'invite_code_optional' => '초대 코드 (선택 사항)',
    'invite_code_required' => '초대 코드 (필수)',
    'email_verify_code' => '이메일 인증 코드',
    'email_verify_code_placeholder' => '인증 코드',

    // Buttons
    'btn_login' => '로그인',
    'btn_webauthn_login' => 'WebAuthn으로 로그인',
    'btn_register' => '새 계정 등록',
    'btn_get_code' => '인증 코드 받기',
    'btn_send_reset_email' => '이메일 발송',
    'btn_reset_password' => '재설정',
    'btn_submit' => '제출',
    'btn_fido_verify' => 'FIDO2로 인증',

    // Checkboxes and links
    'remember_me' => '이 기기 기억하기',
    'forgot_password' => '비밀번호를 잊으셨나요?',
    'no_account' => '아직 계정이 없으신가요?',
    'click_to_register' => '회원가입',
    'already_have_account' => '이미 계정이 있으신가요?',
    'click_to_login' => '로그인',
    'agree_terms_prefix' => '다음을 읽고 동의합니다:',
    'terms_and_privacy' => '이용약관 및 개인정보 처리방침',

    // Hints and notices
    'login_upgrade_notice' => '패널이 업그레이드되었습니다. 비밀번호가 올바르지 않다고 표시되면 비밀번호를 재설정한 후 로그인해 주세요!',
    'reset_password_desc' => '가입된 이메일 주소로 비밀번호 재설정 링크가 포함된 이메일을 보내드립니다.',
    'verify_code_tip' => '인증 코드를 받지 못하셨나요? 권장 이메일 서비스(Outlook, Gmail, iCloud, Protonmail 등)로 변경하시거나 스팸 메일함을 확인하여 저희 이메일을 수신 허용(받은 편지함으로 이동)으로 설정해 주세요!',
    'reset_email_tip' => '이메일을 받지 못하셨다면 스팸 메일함을 확인하시고 저희 이메일을 수신 허용(받은 편지함으로 이동)으로 설정해 주세요!',
    'registration_closed' => '현재 회원가입이 중단되었습니다. 며칠 후 다시 확인해 주세요.',
    'mfa_notice' => '계정에 2단계 인증이 활성화되어 있습니다. 보안을 위해 추가 본인 인증을 완료해 주세요.',
    'oauth_request_failed' => 'OAuth 요청에 실패했습니다',
    'slack_already_bound' => 'Slack 계정이 이미 연동되어 있습니다',
    'discord_already_bound' => 'Discord 계정이 이미 연동되어 있습니다',
    'please_login_first' => '먼저 로그인해 주세요',
    'telegram_already_bound_unbind_first' => 'Telegram이 이미 연동되어 있습니다. 다른 계정으로 변경하려면 먼저 연동을 해제해 주세요',
    'telegram_already_bound' => 'Telegram 계정이 이미 연동되어 있습니다',
    'bind_success' => '성공적으로 연동되었습니다',
    'or_continue_with' => '또는 소셜 계정으로 계속하기',
    'oauth_disabled' => '이 OAuth 로그인은 비활성화되었거나 설정되지 않았습니다',
    'oauth_email_unverified' => '인증되지 않은 OAuth 이메일로는 로그인 또는 가입할 수 없습니다',
    'oauth_already_bound' => '이 OAuth 계정은 이미 다른 사용자에게 연결되어 있습니다',
    'oauth_invite_title' => '초대 코드를 입력하여 가입 완료',
    'oauth_invite_desc' => '인증된 이메일 %email%. 계정 생성을 완료하려면 초대 코드를 입력하세요.',
    'btn_complete_registration' => '가입 완료 및 로그인',
    'oauth_accounts_title' => '소셜 로그인 연결',
    'oauth_accounts_desc' => 'Google 또는 GitHub 계정을 연결하면 원클릭으로 로그인할 수 있습니다.',
    'oauth_bound' => '연결됨',
    'oauth_not_bound' => '연결 안 됨',
    'oauth_bind' => '계정 연결',
];
