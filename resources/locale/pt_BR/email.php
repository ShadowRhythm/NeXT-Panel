<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - E-mail de Verificação',
    'verify_email_title' => 'Verificação de E-mail',
    'verify_code_text' => 'Seu código de verificação de e-mail é: %code%',
    'verify_code_expire' => 'Este código de verificação é válido até %expire%.',
    'verify_code_ignore' => 'Se você não solicitou este código, por favor ignore este e-mail.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - Redefinição de Senha',
    'password_reset_title' => 'Redefinição de Senha',
    'password_reset_desc' => 'Você recebeu este e-mail porque solicitou a redefinição de senha no %app_name%. Se você não solicitou isso, ignore este e-mail.',
    'click_to_reset_password' => 'Clique aqui para redefinir sua senha',

    // New User (new_user.tpl)
    'account_created_title' => 'Conta Criada com Sucesso',

    // Test Email (test.tpl)
    'test_email_subject' => 'E-mail de Teste',
    'test_email_title' => 'Teste de Envio de E-mail',
    'test_email_desc' => 'Este é um e-mail de teste. Se você recebeu isso, sua configuração de e-mail está funcionando corretamente.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - Relatório Diário de Tráfego e Avisos',
    'traffic_report_title' => 'Relatório Diário de Tráfego',
    'username_label' => 'Nome de usuário: %username%',
    'email_label' => 'E-mail: %email%',
    'total_traffic' => 'Tráfego Total: %traffic%',
    'used_traffic' => 'Tráfego Usado: %traffic%',
    'remaining_traffic' => 'Tráfego Restante: %traffic%',
    'today_traffic' => 'Tráfego de Hoje: %traffic%',
    'site_announcement' => 'Aviso do Site:',
    'good_night' => 'Boa noite!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'Relatório Financeiro Diário',
    'finance_weekly_title' => 'Relatório Financeiro Semanal',
    'finance_monthly_title' => 'Relatório Financeiro Mensal',
    'finance_last_week_orders' => 'Total de pedidos da última semana: %count%',
    'finance_last_week_total' => 'Receita total da última semana: %amount%',
    'finance_last_month_orders' => 'Total de pedidos do último mês: %count%',
    'finance_last_month_total' => 'Receita total do último mês: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'Aviso do Sistema',
    'modify_email_settings' => 'Modificar Configurações de E-mail',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - O nível da sua conta expirou',
    'free_traffic_reset_subject' => '%app_name% - Aviso de Reinicialização de Tráfego Gratuito',
    'free_traffic_reset_body' => 'Olá, seu tráfego gratuito foi redefinido para %bandwidth% GB.',
    'low_traffic_subject' => '%app_name% - Seu tráfego restante está baixo',
    'low_traffic_body' => 'Olá, o sistema detectou que seu tráfego restante é inferior a %limit% %unit%.',
    'new_login_subject' => '%app_name% - Notificação de Novo Acesso',
    'new_login_body' => 'Sua conta foi acessada a partir do IP %ip% às %time%',
    'new_sub_subject' => '%app_name% - Notificação de Nova Solicitação de Assinatura',
    'new_sub_body' => 'Sua conta obteve a assinatura a partir do IP %ip% às %time%',
    'ticket_replied_subject' => '%app_name% - Resposta no Ticket',
    'ticket_replied_body' => 'Olá, alguém respondeu ao seu ticket. Por favor, verifique-o.',
    'new_ticket_subject' => '%app_name% - Novo Ticket Criado',
    'new_ticket_body' => 'Administrador, um usuário abriu um novo ticket, por favor atenda-o a tempo.',
    'ticket_replied_admin_body' => 'Administrador, alguém respondeu ao ticket <a href="%ticket_url%">#%ticket_id%</a>, processe-o a tempo.',
    'ticket_replied_user_body' => 'Olá, alguém respondeu ao seu <a href="%ticket_url%">ticket</a>, por favor verifique.',
    'ticket_ai_replied_user_body' => 'Olá, o Assistente de IA respondeu ao seu <a href="%ticket_url%">ticket</a>, por favor verifique.',
    'class_expired_body' => 'Olá, o sistema detectou que o nível da sua conta expirou.',
    'class_expired_reset_body' => 'Seu tráfego foi redefinido para %bandwidth% GB.',
    'auto_renew_disabled_subject' => '%app_name% - Renovação automática desativada',
    'auto_renew_disabled_off_shelf_body' => 'Seu produto [%product%] (Pedido #%order_id%) foi retirado de venda, portanto a renovação automática foi desativada. Visite a loja para escolher outro plano.',
    'auto_renew_failed_subject' => '%app_name% - Falha na cobrança da renovação automática',
    'auto_renew_failed_tabp_body' => 'Seu produto [%product%] (Pedido #%order_id%) está prestes a expirar e a cobrança automática falhou (necessário: %price%, saldo atual: %balance%). Recarregue antes do vencimento para o sistema tentar novamente.',
    'auto_renew_failed_time_body' => 'A cobrança automática do produto [%product%] (Pedido #%order_id%) falhou (necessário: %price%, saldo atual: %balance%). Recarregue seu saldo para o sistema tentar novamente automaticamente.',
    'auto_renew_out_of_stock_subject' => '%app_name% - Estoque esgotado para renovação automática',
    'auto_renew_out_of_stock_body' => 'Seu produto [%product%] (Pedido #%order_id%) está prestes a expirar, mas não foi possível renovar por falta de estoque. Escolha outro plano na loja.',
    'auto_renew_success_subject' => '%app_name% - Renovação automática concluída',
    'auto_renew_success_body' => 'Seu produto [%product%] foi renovado automaticamente. O novo pedido #%order_id% foi criado e %price% foi deduzido do seu saldo.',
];
