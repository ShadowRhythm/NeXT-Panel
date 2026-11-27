<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => 'Entrar na Área do Usuário',
    'register_title' => 'Criar Conta',
    'forgot_password_title' => 'Esqueci Minha Senha',
    'reset_password_title' => 'Definir Nova Senha',
    'mfa_title' => 'Autenticação em Duas Etapas',

    // Form labels and placeholders
    'email' => 'E-mail',
    'email_placeholder' => 'E-mail (Outlook, Gmail, iCloud recomendados)',
    'registered_email' => 'E-mail cadastrado',
    'password' => 'Senha',
    'password_placeholder' => 'Senha',
    'confirm_password' => 'Confirmar Senha',
    'confirm_password_placeholder' => 'Repita a senha',
    'new_password' => 'Nova Senha',
    'new_password_placeholder' => 'Por favor, insira a nova senha',
    'confirm_new_password' => 'Confirmar Nova Senha',
    'confirm_new_password_placeholder' => 'Por favor, confirme a nova senha',
    'nickname' => 'Apelido',
    'invite_code_optional' => 'Código de Convite (Opcional)',
    'invite_code_required' => 'Código de Convite (Obrigatório)',
    'email_verify_code' => 'Código de Verificação de E-mail',
    'email_verify_code_placeholder' => 'Código de verificação',

    // Buttons
    'btn_login' => 'Entrar',
    'btn_webauthn_login' => 'Entrar com WebAuthn',
    'btn_register' => 'Registrar Nova Conta',
    'btn_get_code' => 'Obter Código',
    'btn_send_reset_email' => 'Enviar E-mail',
    'btn_reset_password' => 'Redefinir',
    'btn_submit' => 'Enviar',
    'btn_fido_verify' => 'Verificar com FIDO2',

    // Checkboxes and links
    'remember_me' => 'Lembrar deste dispositivo',
    'forgot_password' => 'Esqueceu a senha?',
    'no_account' => 'Ainda não tem uma conta?',
    'click_to_register' => 'Cadastre-se',
    'already_have_account' => 'Já tem uma conta?',
    'click_to_login' => 'Entrar',
    'agree_terms_prefix' => 'Li e concordo com os',
    'terms_and_privacy' => 'Termos de Serviço e Política de Privacidade',

    // Hints and notices
    'login_upgrade_notice' => 'O painel foi atualizado. Se a sua senha estiver incorreta, redefina-a antes de entrar!',
    'reset_password_desc' => 'Enviaremos um e-mail para o seu endereço cadastrado contendo um link para redefinir sua senha.',
    'verify_code_tip' => 'Não recebeu o código? Tente usar um provedor recomendado (Outlook, Gmail, iCloud, Protonmail etc.) ou verifique sua caixa de spam e adicione nosso e-mail à lista de remetentes confiáveis (mova para a caixa de entrada)!',
    'reset_email_tip' => 'Se não recebeu o e-mail, verifique sua caixa de spam e adicione nosso e-mail à lista de remetentes confiáveis (mova para a caixa de entrada)!',
    'registration_closed' => 'O registro está temporariamente fechado. Por favor, volte em alguns dias.',
    'mfa_notice' => 'A autenticação em duas etapas está ativada na sua conta. Para sua segurança, conclua a verificação adicional de identidade.',
    'oauth_request_failed' => 'Falha na solicitação OAuth',
    'slack_already_bound' => 'Conta do Slack já vinculada',
    'discord_already_bound' => 'Conta do Discord já vinculada',
    'please_login_first' => 'Por favor, faça login primeiro',
    'telegram_already_bound_unbind_first' => 'Você já vinculou o Telegram. Se precisar alterar, desvincule primeiro',
    'telegram_already_bound' => 'Conta do Telegram já vinculada',
    'bind_success' => 'Vinculado com sucesso',
    'or_continue_with' => 'Ou continuar com',
    'oauth_disabled' => 'Este provedor OAuth está desativado ou não configurado',
    'oauth_email_unverified' => 'O e-mail da sua conta OAuth não foi verificado',
    'oauth_already_bound' => 'Esta conta OAuth já está vinculada a outro usuário',
    'oauth_invite_title' => 'Inserir código de convite',
    'oauth_invite_desc' => 'E-mail verificado %email%. Insira um código de convite para concluir seu cadastro.',
    'btn_complete_registration' => 'Concluir cadastro',
    'oauth_accounts_title' => 'Contas de login social',
    'oauth_accounts_desc' => 'Vincule sua conta Google ou GitHub para entrar com um clique.',
    'oauth_bound' => 'Vinculado',
    'oauth_not_bound' => 'Não vinculado',
    'oauth_bind' => 'Vincular conta',
];
