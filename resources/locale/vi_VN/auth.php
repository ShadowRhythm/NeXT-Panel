<?php

declare(strict_types=1);

return [
    // Page titles and headings
    'login_title' => 'Đăng nhập Trung tâm người dùng',
    'register_title' => 'Đăng ký tài khoản',
    'forgot_password_title' => 'Quên mật khẩu',
    'reset_password_title' => 'Đặt mật khẩu mới',
    'mfa_title' => 'Xác thực hai yếu tố',

    // Form labels and placeholders
    'email' => 'Email',
    'email_placeholder' => 'Email (khuyên dùng Outlook, Gmail, iCloud)',
    'registered_email' => 'Email đã đăng ký',
    'password' => 'Mật khẩu',
    'password_placeholder' => 'Mật khẩu',
    'confirm_password' => 'Xác nhận mật khẩu',
    'confirm_password_placeholder' => 'Nhập lại mật khẩu',
    'new_password' => 'Mật khẩu mới',
    'new_password_placeholder' => 'Vui lòng nhập mật khẩu mới',
    'confirm_new_password' => 'Nhập lại mật khẩu mới',
    'confirm_new_password_placeholder' => 'Vui lòng nhập lại mật khẩu mới',
    'nickname' => 'Biệt danh',
    'invite_code_optional' => 'Mã mời (Không bắt buộc)',
    'invite_code_required' => 'Mã mời (Bắt buộc)',
    'email_verify_code' => 'Mã xác nhận Email',
    'email_verify_code_placeholder' => 'Mã xác nhận',

    // Buttons
    'btn_login' => 'Đăng nhập',
    'btn_webauthn_login' => 'Đăng nhập bằng WebAuthn',
    'btn_register' => 'Đăng ký tài khoản mới',
    'btn_get_code' => 'Lấy mã',
    'btn_send_reset_email' => 'Gửi Email',
    'btn_reset_password' => 'Đặt lại',
    'btn_submit' => 'Gửi',
    'btn_fido_verify' => 'Xác minh bằng FIDO2',

    // Checkboxes and links
    'remember_me' => 'Ghi nhớ thiết bị này',
    'forgot_password' => 'Quên mật khẩu',
    'no_account' => 'Chưa có tài khoản?',
    'click_to_register' => 'Đăng ký ngay',
    'already_have_account' => 'Đã có tài khoản?',
    'click_to_login' => 'Đăng nhập',
    'agree_terms_prefix' => 'Tôi đã đọc và đồng ý với',
    'terms_and_privacy' => 'Điều khoản dịch vụ và Chính sách bảo mật',

    // Hints and notices
    'login_upgrade_notice' => 'Bảng điều khiển đã được nâng cấp. Nếu mật khẩu không chính xác, vui lòng đặt lại mật khẩu trước khi đăng nhập!',
    'reset_password_desc' => 'Chúng tôi sẽ gửi một email đến địa chỉ email đã đăng ký của bạn kèm theo liên kết để đặt lại mật khẩu.',
    'verify_code_tip' => 'Không nhận được mã? Vui lòng đổi sang nhà cung cấp email được khuyên dùng (Outlook, Gmail, iCloud, Protonmail, v.v.) hoặc kiểm tra thư mục Thư rác (Spam) và đánh dấu tin cậy email của chúng tôi (chuyển về Hộp thư đến)!',
    'reset_email_tip' => 'Nếu bạn chưa nhận được email, vui lòng kiểm tra thư mục Thư rác (Spam) và đánh dấu tin cậy email của chúng tôi (chuyển về Hộp thư đến)!',
    'registration_closed' => 'Hiện tại đang đóng đăng ký. Vui lòng quay lại sau vài ngày.',
    'mfa_notice' => 'Tài khoản của bạn đã bật xác thực hai yếu tố. Để đảm bảo an toàn, vui lòng hoàn tất bước xác minh danh tính bổ sung.',
    'oauth_request_failed' => 'Yêu cầu OAuth thất bại',
    'slack_already_bound' => 'Tài khoản Slack đã được liên kết',
    'discord_already_bound' => 'Tài khoản Discord đã được liên kết',
    'please_login_first' => 'Vui lòng đăng nhập trước',
    'telegram_already_bound_unbind_first' => 'Telegram đã được liên kết. Vui lòng hủy liên kết trước nếu bạn muốn đổi tài khoản',
    'telegram_already_bound' => 'Tài khoản Telegram đã được liên kết',
    'bind_success' => 'Liên kết thành công',
    'or_continue_with' => 'Hoặc tiếp tục với',
    'oauth_disabled' => 'Nhà cung cấp OAuth này đã bị tắt hoặc chưa cấu hình',
    'oauth_email_unverified' => 'Email tài khoản OAuth chưa được xác minh',
    'oauth_already_bound' => 'Tài khoản OAuth này đã được liên kết với người dùng khác',
    'oauth_invite_title' => 'Nhập mã mời để hoàn tất đăng ký',
    'oauth_invite_desc' => 'Email đã xác minh: %email%. Vui lòng nhập mã mời để tạo tài khoản.',
    'btn_complete_registration' => 'Hoàn tất đăng ký',
    'oauth_accounts_title' => 'Liên kết đăng nhập mạng xã hội',
    'oauth_accounts_desc' => 'Liên kết Google hoặc GitHub để đăng nhập nhanh chỉ với một chạm.',
    'oauth_bound' => 'Đã liên kết',
    'oauth_not_bound' => 'Chưa liên kết',
    'oauth_bind' => 'Liên kết tài khoản',
];
