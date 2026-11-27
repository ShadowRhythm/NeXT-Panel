<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => 'تسجيل الدخول إلى لوحة المستخدم',
    'register_title' => 'إنشاء حساب جديد',
    'forgot_password_title' => 'نسيت كلمة المرور',
    'reset_password_title' => 'تعيين كلمة مرور جديدة',
    'mfa_title' => 'المصادقة الثنائية',

    // Form labels and placeholders
    'email' => 'البريد الإلكتروني',
    'email_placeholder' => 'البريد الإلكتروني (يُفضّل Outlook أو Gmail أو iCloud)',
    'registered_email' => 'البريد الإلكتروني المسجّل',
    'password' => 'كلمة المرور',
    'password_placeholder' => 'كلمة المرور',
    'confirm_password' => 'تأكيد كلمة المرور',
    'confirm_password_placeholder' => 'أعد إدخال كلمة المرور',
    'new_password' => 'كلمة المرور الجديدة',
    'new_password_placeholder' => 'يرجى إدخال كلمة المرور الجديدة',
    'confirm_new_password' => 'تأكيد كلمة المرور الجديدة',
    'confirm_new_password_placeholder' => 'يرجى إعادة إدخال كلمة المرور الجديدة',
    'nickname' => 'الاسم المستعار',
    'invite_code_optional' => 'رمز الدعوة (اختياري)',
    'invite_code_required' => 'رمز الدعوة (إلزامي)',
    'email_verify_code' => 'رمز التحقق عبر البريد الإلكتروني',
    'email_verify_code_placeholder' => 'رمز التحقق',

    // Buttons
    'btn_login' => 'تسجيل الدخول',
    'btn_webauthn_login' => 'تسجيل الدخول عبر WebAuthn',
    'btn_register' => 'تسجيل حساب جديد',
    'btn_get_code' => 'الحصول على الرمز',
    'btn_send_reset_email' => 'إرسال البريد الإلكتروني',
    'btn_reset_password' => 'إعادة تعيين',
    'btn_submit' => 'إرسال',
    'btn_fido_verify' => 'التحقق عبر FIDO2',

    // Checkboxes and links
    'remember_me' => 'تذكر هذا الجهاز',
    'forgot_password' => 'نسيت كلمة المرور',
    'no_account' => 'ليس لديك حساب بعد؟',
    'click_to_register' => 'إنشاء حساب',
    'already_have_account' => 'هل لديك حساب بالفعل؟',
    'click_to_login' => 'تسجيل الدخول',
    'agree_terms_prefix' => 'لقد قرأت وأوافق على',
    'terms_and_privacy' => 'شروط الخدمة وسياسة الخصوصية',

    // Hints and notices
    'login_upgrade_notice' => 'تمت ترقية اللوحة. إذا كانت كلمة المرور غير صحيحة، يرجى إعادة تعيين كلمة المرور قبل تسجيل الدخول!',
    'reset_password_desc' => 'سنرسل رسالة إلى بريدك الإلكتروني المسجّل تحتوي على رابط لإعادة تعيين كلمة المرور.',
    'verify_code_tip' => 'لم تستلم الرمز؟ يرجى التبديل إلى مزوّد بريد إلكتروني موصى به (مثل Outlook أو Gmail أو iCloud أو Protonmail) أو التحقق من مجلد البريد العشوائي (Spam) وإضافة رسائلنا إلى القائمة البيضاء!',
    'reset_email_tip' => 'إذا لم تستلم الرسالة، يرجى التحقق من مجلد البريد العشوائي (Spam) وإضافة رسائلنا إلى القائمة البيضاء!',
    'registration_closed' => 'التسجيل مغلق حاليًا. يرجى المحاولة مرة أخرى بعد بضعة أيام.',
    'mfa_notice' => 'المصادقة الثنائية مفعّلة لحسابك. لحمايتك، يرجى إكمال التحقق الإضافي من الهوية.',
    'oauth_request_failed' => 'فشل طلب OAuth',
    'slack_already_bound' => 'حساب Slack مرتبط بالفعل',
    'discord_already_bound' => 'حساب Discord مرتبط بالفعل',
    'please_login_first' => 'يرجى تسجيل الدخول أولًا',
    'telegram_already_bound_unbind_first' => 'حساب تيليجرام مرتبط بالفعل. يرجى فك الربط أولًا إذا كنت ترغب في تبديل الحساب',
    'telegram_already_bound' => 'حساب تيليجرام مرتبط بالفعل',
    'bind_success' => 'تم الربط بنجاح',
    'or_continue_with' => 'أو المتابعة باستخدام',
    'oauth_disabled' => 'مزود تسجيل الدخول هذا معطل أو غير مُعد',
    'oauth_email_unverified' => 'البريد الإلكتروني لحساب OAuth غير موثق',
    'oauth_already_bound' => 'حساب OAuth هذا مرتبط بالفعل بمستخدم آخر',
    'oauth_invite_title' => 'أدخل رمز الدعوة لإكمال التسجيل',
    'oauth_invite_desc' => 'تم التحقق من البريد %email%. أدخل رمز الدعوة لإكمال إنشاء الحساب.',
    'btn_complete_registration' => 'إكمال التسجيل',
    'oauth_accounts_title' => 'ربط حسابات تسجيل الدخول',
    'oauth_accounts_desc' => 'اربط حساب Google أو GitHub لتسجيل الدخول بنقرة واحدة.',
    'oauth_bound' => 'مرتبط',
    'oauth_not_bound' => 'غير مرتبط',
    'oauth_bind' => 'ربط الحساب',
];
