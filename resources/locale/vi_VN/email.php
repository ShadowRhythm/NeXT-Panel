<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - Email xác minh',
    'verify_email_title' => 'Xác minh Email',
    'verify_code_text' => 'Mã xác minh email bạn đã yêu cầu là: %code%',
    'verify_code_expire' => 'Mã xác minh này có hiệu lực đến %expire%.',
    'verify_code_ignore' => 'Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - Đặt lại mật khẩu',
    'password_reset_title' => 'Đặt lại mật khẩu',
    'password_reset_desc' => 'Bạn nhận được email này vì bạn đã yêu cầu đặt lại mật khẩu trên %app_name%. Nếu không phải bạn yêu cầu, vui lòng bỏ qua email này.',
    'click_to_reset_password' => 'Nhấn vào đây để đặt lại mật khẩu của bạn',

    // New User (new_user.tpl)
    'account_created_title' => 'Tài khoản đã được tạo',

    // Test Email (test.tpl)
    'test_email_subject' => 'Email thử nghiệm',
    'test_email_title' => 'Kiểm tra gửi Email',
    'test_email_desc' => 'Đây là email thử nghiệm. Nếu bạn nhận được email này, cấu hình email của bạn đang hoạt động bình thường.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - Báo cáo lưu lượng hằng ngày & Thông báo',
    'traffic_report_title' => 'Báo cáo lưu lượng hằng ngày',
    'username_label' => 'Tên người dùng: %username%',
    'email_label' => 'Email: %email%',
    'total_traffic' => 'Tổng lưu lượng: %traffic%',
    'used_traffic' => 'Lưu lượng đã dùng: %traffic%',
    'remaining_traffic' => 'Lưu lượng còn lại: %traffic%',
    'today_traffic' => 'Lưu lượng hôm nay: %traffic%',
    'site_announcement' => 'Thông báo trang web:',
    'good_night' => 'Chúc ngủ ngon!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => 'Báo cáo tài chính hằng ngày',
    'finance_weekly_title' => 'Báo cáo tài chính hằng tuần',
    'finance_monthly_title' => 'Báo cáo tài chính hằng tháng',
    'finance_last_week_orders' => 'Tổng số đơn hàng tuần trước: %count%',
    'finance_last_week_total' => 'Tổng doanh thu tuần trước: %amount%',
    'finance_last_month_orders' => 'Tổng số đơn hàng tháng trước: %count%',
    'finance_last_month_total' => 'Tổng doanh thu tháng trước: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => 'Thông báo hệ thống',
    'modify_email_settings' => 'Thay đổi cài đặt Email',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - Cấp độ tài khoản của bạn đã hết hạn',
    'free_traffic_reset_subject' => '%app_name% - Thông báo đặt lại lưu lượng miễn phí',
    'free_traffic_reset_body' => 'Xin chào, lưu lượng miễn phí của bạn đã được đặt lại thành %bandwidth% GB.',
    'low_traffic_subject' => '%app_name% - Lưu lượng còn lại của bạn sắp hết',
    'low_traffic_body' => 'Xin chào, hệ thống nhận thấy lưu lượng còn lại của bạn đang thấp hơn %limit% %unit%.',
    'new_login_subject' => '%app_name% - Thông báo đăng nhập mới',
    'new_login_body' => 'Tài khoản của bạn đã đăng nhập vào bảng điều khiển người dùng từ IP %ip% lúc %time%',
    'new_sub_subject' => '%app_name% - Thông báo đăng ký mới',
    'new_sub_body' => 'Tài khoản của bạn đã cập nhật đăng ký từ IP %ip% lúc %time%',
    'ticket_replied_subject' => '%app_name% - Phiếu hỗ trợ đã có phản hồi',
    'ticket_replied_body' => 'Xin chào, có người đã phản hồi phiếu hỗ trợ của bạn, vui lòng kiểm tra.',
    'new_ticket_subject' => '%app_name% - Phiếu hỗ trợ mới được tạo',
    'new_ticket_body' => 'Quản trị viên ơi, có người vừa mở phiếu hỗ trợ mới, vui lòng xử lý kịp thời.',
    'ticket_replied_admin_body' => 'Quản trị viên ơi, có người đã phản hồi phiếu hỗ trợ <a href="%ticket_url%">#%ticket_id%</a>, vui lòng xử lý kịp thời.',
    'ticket_replied_user_body' => 'Xin chào, có người đã phản hồi <a href="%ticket_url%">phiếu hỗ trợ</a> của bạn, vui lòng kiểm tra.',
    'ticket_ai_replied_user_body' => 'Xin chào, Trợ lý AI đã phản hồi <a href="%ticket_url%">phiếu hỗ trợ</a> của bạn, vui lòng kiểm tra.',
    'class_expired_body' => 'Xin chào, hệ thống nhận thấy cấp độ tài khoản của bạn đã hết hạn.',
    'class_expired_reset_body' => 'Lưu lượng của bạn đã được đặt lại thành %bandwidth% GB.',
    'auto_renew_disabled_subject' => '%app_name% - Đã tắt tự động gia hạn đơn hàng',
    'auto_renew_disabled_off_shelf_body' => 'Sản phẩm [%product%] (Đơn hàng #%order_id%) của bạn đã ngừng bán hoặc bị gỡ khỏi kệ hàng, nên tính năng tự động gia hạn đã bị tắt. Vui lòng truy cập cửa hàng để chọn gói dịch vụ khác.',
    'auto_renew_failed_subject' => '%app_name% - Thanh toán tự động gia hạn đơn hàng thất bại',
    'auto_renew_failed_tabp_body' => 'Sản phẩm [%product%] (Đơn hàng #%order_id%) của bạn sắp hết hạn và việc trừ tiền tự động gia hạn đã thất bại (cần %price%, số dư hiện tại %balance%). Vui lòng nạp tiền kịp thời trước khi hết hạn để hệ thống thử lại; sau khi hết hạn hệ thống sẽ không thử lại nữa.',
    'auto_renew_failed_time_body' => 'Việc trừ tiền tự động gia hạn cho sản phẩm [%product%] (Đơn hàng #%order_id%) của bạn đã thất bại (cần %price%, số dư hiện tại %balance%). Vui lòng nạp tiền kịp thời, hệ thống sẽ tự động thử lại.',
    'auto_renew_out_of_stock_subject' => '%app_name% - Tự động gia hạn đơn hàng thất bại do hết hàng',
    'auto_renew_out_of_stock_body' => 'Sản phẩm [%product%] (Đơn hàng #%order_id%) của bạn sắp hết hạn và không thể tự động gia hạn do sản phẩm tạm thời hết hàng. Vui lòng kiểm tra lại sau hoặc chọn gói dịch vụ khác tại cửa hàng.',
    'auto_renew_success_subject' => '%app_name% - Tự động gia hạn đơn hàng thành công',
    'auto_renew_success_body' => 'Sản phẩm [%product%] của bạn đã được tự động gia hạn thành công. Đơn hàng mới #%order_id% đã được tạo và %price% đã được trừ khỏi số dư của bạn.',
];
