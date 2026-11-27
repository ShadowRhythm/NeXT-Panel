<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => 'Вход в личный кабинет',
    'register_title' => 'Регистрация аккаунта',
    'forgot_password_title' => 'Забыли пароль',
    'reset_password_title' => 'Установка нового пароля',
    'mfa_title' => 'Двухфакторная аутентификация',

    // Form labels and placeholders
    'email' => 'Email',
    'email_placeholder' => 'Email (рекомендуется Outlook, Gmail, iCloud)',
    'registered_email' => 'Зарегистрированный email',
    'password' => 'Пароль',
    'password_placeholder' => 'Пароль',
    'confirm_password' => 'Подтвердите пароль',
    'confirm_password_placeholder' => 'Повторите пароль',
    'new_password' => 'Новый пароль',
    'new_password_placeholder' => 'Пожалуйста, введите новый пароль',
    'confirm_new_password' => 'Повторите новый пароль',
    'confirm_new_password_placeholder' => 'Пожалуйста, введите новый пароль еще раз',
    'nickname' => 'Никнейм',
    'invite_code_optional' => 'Код приглашения (необязательно)',
    'invite_code_required' => 'Код приглашения (обязательно)',
    'email_verify_code' => 'Код подтверждения из email',
    'email_verify_code_placeholder' => 'Код подтверждения',

    // Buttons
    'btn_login' => 'Войти',
    'btn_webauthn_login' => 'Войти с WebAuthn',
    'btn_register' => 'Зарегистрировать аккаунт',
    'btn_get_code' => 'Получить код',
    'btn_send_reset_email' => 'Отправить письмо',
    'btn_reset_password' => 'Сбросить',
    'btn_submit' => 'Отправить',
    'btn_fido_verify' => 'Подтвердить с FIDO2',

    // Checkboxes and links
    'remember_me' => 'Запомнить это устройство',
    'forgot_password' => 'Забыли пароль?',
    'no_account' => 'Ещё нет аккаунта?',
    'click_to_register' => 'Зарегистрироваться',
    'already_have_account' => 'Уже есть аккаунт?',
    'click_to_login' => 'Войти',
    'agree_terms_prefix' => 'Я прочитал(а) и принимаю',
    'terms_and_privacy' => 'Условия обслуживания и Политику конфиденциальности',

    // Hints and notices
    'login_upgrade_notice' => 'Панель была обновлена. Если пароль не подходит, пожалуйста, сбросьте пароль перед входом!',
    'reset_password_desc' => 'Мы отправим на ваш зарегистрированный email письмо со ссылкой для сброса пароля.',
    'verify_code_tip' => 'Не пришел код? Используйте рекомендуемую почту (Outlook, Gmail, iCloud, Protonmail и др.) или проверьте папку «Спам» и добавьте наш адрес в белый список (переместите во «Входящие»)!',
    'reset_email_tip' => 'Если вы не получили письмо, проверьте папку «Спам» и добавьте наш адрес в белый список (переместите во «Входящие»)!',
    'registration_closed' => 'Регистрация в данный момент закрыта. Пожалуйста, загляните через несколько дней.',
    'mfa_notice' => 'Для вашего аккаунта включена двухфакторная аутентификация. В целях безопасности пройдите дополнительную проверку.',
    'oauth_request_failed' => 'Сбой запроса OAuth',
    'slack_already_bound' => 'Аккаунт Slack уже привязан',
    'discord_already_bound' => 'Аккаунт Discord уже привязан',
    'please_login_first' => 'Пожалуйста, сначала войдите',
    'telegram_already_bound_unbind_first' => 'Telegram уже привязан. Чтобы изменить привязку, сначала отвяжите его',
    'telegram_already_bound' => 'Аккаунт Telegram уже привязан',
    'bind_success' => 'Успешно привязано',
    'or_continue_with' => 'Или войти через',
    'oauth_disabled' => 'Этот OAuth-провайдер отключён или не настроен',
    'oauth_email_unverified' => 'Email аккаунта OAuth не подтверждён',
    'oauth_already_bound' => 'Этот аккаунт OAuth уже привязан к другому пользователю',
    'oauth_invite_title' => 'Введите код приглашения',
    'oauth_invite_desc' => 'Подтверждённый email: %email%. Введите код приглашения для завершения регистрации.',
    'btn_complete_registration' => 'Завершить регистрацию',
    'oauth_accounts_title' => 'Вход через соцсети',
    'oauth_accounts_desc' => 'Привяжите Google или GitHub для быстрого входа в панель.',
    'oauth_bound' => 'Привязано',
    'oauth_not_bound' => 'Не привязано',
    'oauth_bind' => 'Привязать',
];
