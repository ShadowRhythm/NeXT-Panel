<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - Verificación de correo electrónico',
    'verify_email_title' => 'Verificación de correo electrónico',
    'verify_code_text' => 'Tu código de verificación por correo es: %code%',
    'verify_code_expire' => 'Este código de verificación es válido hasta el %expire%.',
    'verify_code_ignore' => 'Si no has solicitado este código, ignora este correo.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - Restablecimiento de contraseña',
    'password_reset_title' => 'Restablecimiento de contraseña',
    'password_reset_desc' => 'Has recibido este correo porque solicitaste restablecer tu contraseña en %app_name%. Si no fuiste tú, ignora este mensaje.',
    'click_to_reset_password' => 'Haz clic aquí para restablecer tu contraseña',

    // New User (new_user.tpl)
    'account_created_title' => 'Cuenta creada',

    // Test Email (test.tpl)
    'test_email_subject' => 'Correo de prueba',
    'test_email_title' => 'Prueba de envío de correo',
    'test_email_desc' => 'Este es un correo de prueba. Si lo estás leyendo, tu configuración de correo funciona correctamente.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - Informe diario de tráfico y anuncios',
    'traffic_report_title' => 'Informe diario de tráfico',
    'username_label' => 'Nombre de usuario: %username%',
    'email_label' => 'Correo electrónico: %email%',
    'total_traffic' => 'Tráfico total: %traffic%',
    'used_traffic' => 'Tráfico usado: %traffic%',
    'remaining_traffic' => 'Tráfico restante: %traffic%',
    'today_traffic' => 'Tráfico de hoy: %traffic%',
    'site_announcement' => 'Anuncio del sitio:',
    'good_night' => '¡Buenas noches!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'Informe financiero diario',
    'finance_weekly_title' => 'Informe financiero semanal',
    'finance_monthly_title' => 'Informe financiero mensual',
    'finance_last_week_orders' => 'Total de pedidos de la semana pasada: %count%',
    'finance_last_week_total' => 'Ingresos totales de la semana pasada: %amount%',
    'finance_last_month_orders' => 'Total de pedidos del mes pasado: %count%',
    'finance_last_month_total' => 'Ingresos totales del mes pasado: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'Aviso del sistema',
    'modify_email_settings' => 'Modificar preferencias de correo',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - Tu nivel de cuenta ha expirado',
    'free_traffic_reset_subject' => '%app_name% - Aviso de restablecimiento de tráfico gratuito',
    'free_traffic_reset_body' => 'Hola, tu tráfico gratuito se ha restablecido a %bandwidth% GB.',
    'low_traffic_subject' => '%app_name% - Te queda poco tráfico disponible',
    'low_traffic_body' => 'Hola, el sistema ha detectado que tu tráfico restante es inferior a %limit% %unit%.',
    'new_login_subject' => '%app_name% - Notificación de nuevo inicio de sesión',
    'new_login_body' => 'Tu cuenta ha iniciado sesión en el panel de usuario desde la IP %ip% el %time%',
    'new_sub_subject' => '%app_name% - Notificación de nueva suscripción',
    'new_sub_body' => 'Tu cuenta ha actualizado la suscripción desde la IP %ip% el %time%',
    'ticket_replied_subject' => '%app_name% - Respuesta en tu ticket',
    'ticket_replied_body' => 'Hola, alguien ha respondido a tu ticket; revísalo cuando puedas.',
    'new_ticket_subject' => '%app_name% - Nuevo ticket creado',
    'new_ticket_body' => 'Administrador, alguien ha abierto un nuevo ticket; atiéndelo a tiempo.',
    'ticket_replied_admin_body' => 'Administrador, alguien ha respondido al ticket <a href="%ticket_url%">#%ticket_id%</a>; atiéndelo a tiempo.',
    'ticket_replied_user_body' => 'Hola, alguien ha respondido a tu <a href="%ticket_url%">ticket</a>; revísalo cuando puedas.',
    'ticket_ai_replied_user_body' => 'Hola, el asistente de IA ha respondido a tu <a href="%ticket_url%">ticket</a>; revísalo cuando puedas.',
    'class_expired_body' => 'Hola, el sistema ha detectado que tu nivel de cuenta ha expirado.',
    'class_expired_reset_body' => 'Tu tráfico se ha restablecido a %bandwidth% GB.',
    'auto_renew_disabled_subject' => '%app_name% - Renovación automática desactivada',
    'auto_renew_disabled_off_shelf_body' => 'Tu producto [%product%] (Pedido #%order_id%) ha sido retirado de la venta o descatalogado, por lo que la renovación automática se ha desactivado. Visita la tienda para elegir otro paquete.',
    'auto_renew_failed_subject' => '%app_name% - Error en el cobro de renovación automática',
    'auto_renew_failed_tabp_body' => 'Tu producto [%product%] (Pedido #%order_id%) está a punto de expirar y ha fallado el cobro de la renovación automática (se requieren %price%, saldo actual %balance%). Recarga saldo antes de que expire para que el sistema pueda reintentarlo; tras la expiración no se realizarán más intentos.',
    'auto_renew_failed_time_body' => 'Ha fallado el cobro de la renovación automática de tu producto [%product%] (Pedido #%order_id%) (se requieren %price%, saldo actual %balance%). Recarga saldo a tiempo y el sistema lo reintentará automáticamente.',
    'auto_renew_out_of_stock_subject' => '%app_name% - Renovación automática sin stock',
    'auto_renew_out_of_stock_body' => 'Tu producto [%product%] (Pedido #%order_id%) está a punto de expirar y la renovación automática ha fallado porque el producto está temporalmente agotado. Vuelve a comprobarlo o elige otro paquete en la tienda.',
    'auto_renew_success_subject' => '%app_name% - Renovación automática completada',
    'auto_renew_success_body' => 'Tu producto [%product%] se ha renovado automáticamente. Se ha creado el nuevo pedido #%order_id% y se han descontado %price% de tu saldo.',
];
