<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => 'Iniciar sesión en el Centro de Usuarios',
    'register_title' => 'Crear cuenta',
    'forgot_password_title' => 'Recuperar contraseña',
    'reset_password_title' => 'Establecer nueva contraseña',
    'mfa_title' => 'Autenticación en dos pasos',

    // Form labels and placeholders
    'email' => 'Correo electrónico',
    'email_placeholder' => 'Correo electrónico (se recomienda Outlook, Gmail o iCloud)',
    'registered_email' => 'Correo electrónico registrado',
    'password' => 'Contraseña',
    'password_placeholder' => 'Contraseña',
    'confirm_password' => 'Confirmar contraseña',
    'confirm_password_placeholder' => 'Repite la contraseña',
    'new_password' => 'Nueva contraseña',
    'new_password_placeholder' => 'Introduce tu nueva contraseña',
    'confirm_new_password' => 'Confirmar nueva contraseña',
    'confirm_new_password_placeholder' => 'Vuelve a introducir tu nueva contraseña',
    'nickname' => 'Apodo',
    'invite_code_optional' => 'Código de invitación (opcional)',
    'invite_code_required' => 'Código de invitación (obligatorio)',
    'email_verify_code' => 'Código de verificación por correo',
    'email_verify_code_placeholder' => 'Código de verificación',

    // Buttons
    'btn_login' => 'Iniciar sesión',
    'btn_webauthn_login' => 'Iniciar sesión con WebAuthn',
    'btn_register' => 'Registrar nueva cuenta',
    'btn_get_code' => 'Obtener código',
    'btn_send_reset_email' => 'Enviar correo',
    'btn_reset_password' => 'Restablecer',
    'btn_submit' => 'Enviar',
    'btn_fido_verify' => 'Verificar con FIDO2',

    // Checkboxes and links
    'remember_me' => 'Recordar este dispositivo',
    'forgot_password' => '¿Olvidaste tu contraseña?',
    'no_account' => '¿Aún no tienes cuenta?',
    'click_to_register' => 'Regístrate',
    'already_have_account' => '¿Ya tienes una cuenta?',
    'click_to_login' => 'Inicia sesión',
    'agree_terms_prefix' => 'He leído y acepto los',
    'terms_and_privacy' => 'Términos del servicio y Política de privacidad',

    // Hints and notices
    'login_upgrade_notice' => 'El panel se ha actualizado. Si la contraseña no funciona, restablécela antes de iniciar sesión.',
    'reset_password_desc' => 'Te enviaremos un correo a tu dirección registrada con un enlace para restablecer tu contraseña.',
    'verify_code_tip' => '¿No recibiste el código? Prueba con un proveedor recomendado (Outlook, Gmail, iCloud, Protonmail, etc.) o revisa tu carpeta de spam y marca nuestros correos como seguros.',
    'reset_email_tip' => 'Si no encuentras el correo, revisa tu carpeta de spam y añade nuestra dirección a tu lista blanca.',
    'registration_closed' => 'El registro está cerrado temporalmente. Vuelve a intentarlo en unos días.',
    'mfa_notice' => 'La autenticación en dos pasos está activada en tu cuenta. Por tu seguridad, completa la verificación de identidad adicional.',
    'oauth_request_failed' => 'Error en la solicitud OAuth',
    'slack_already_bound' => 'Esta cuenta de Slack ya está vinculada',
    'discord_already_bound' => 'Esta cuenta de Discord ya está vinculada',
    'please_login_first' => 'Por favor, inicia sesión primero',
    'telegram_already_bound_unbind_first' => 'Telegram ya está vinculado. Desvincúlalo primero si deseas cambiar de cuenta',
    'telegram_already_bound' => 'Esta cuenta de Telegram ya está vinculada',
    'bind_success' => 'Vinculación completada correctamente',
];
