<?php

declare(strict_types=1);

return [
    // Verify Email (verify_code.tpl)
    'verify_email_subject' => '%app_name% - 이메일 인증',
    'verify_email_title' => '이메일 인증',
    'verify_code_text' => '요청하신 이메일 인증 코드는 다음과 같습니다: %code%',
    'verify_code_expire' => '이 인증 코드는 %expire%까지 유효합니다.',
    'verify_code_ignore' => '본인이 요청하지 않은 경우 이 이메일을 무시해 주세요.',

    // Password Reset (password_reset.tpl)
    'password_reset_subject' => '%app_name% - 비밀번호 재설정',
    'password_reset_title' => '비밀번호 재설정',
    'password_reset_desc' => '%app_name%에서 비밀번호 재설정을 요청하셨기 때문에 이 이메일이 발송되었습니다. 본인이 요청하지 않으셨다면 이 이메일을 무시해 주세요.',
    'click_to_reset_password' => '여기를 클릭하여 비밀번호 재설정',

    // New User (new_user.tpl)
    'account_created_title' => '계정 생성 완료',

    // Test Email (test.tpl)
    'test_email_subject' => '테스트 이메일',
    'test_email_title' => '이메일 발송 테스트',
    'test_email_desc' => '테스트 이메일입니다. 이 메일을 수신하셨다면 이메일 발송 설정이 정상적으로 작동하고 있는 것입니다.',

    // Traffic Report (traffic_report.tpl)
    'traffic_report_subject' => '%app_name% - 일일 트래픽 리포트 및 공지사항',
    'traffic_report_title' => '일일 트래픽 리포트',
    'username_label' => '사용자 이름: %username%',
    'email_label' => '이메일: %email%',
    'total_traffic' => '전체 트래픽: %traffic%',
    'used_traffic' => '사용한 트래픽: %traffic%',
    'remaining_traffic' => '남은 트래픽: %traffic%',
    'today_traffic' => '오늘 사용 트래픽: %traffic%',
    'site_announcement' => '사이트 공지사항:',
    'good_night' => '좋은 밤 되세요!',

    // Financial Reports (finance.tpl)
    'finance_daily_title' => '일일 재무 리포트',
    'finance_weekly_title' => '주간 재무 리포트',
    'finance_monthly_title' => '월간 재무 리포트',
    'finance_last_week_orders' => '지난주 총 주문 건수: %count%',
    'finance_last_week_total' => '지난주 총 수익: %amount%',
    'finance_last_month_orders' => '지난달 총 주문 건수: %count%',
    'finance_last_month_total' => '지난달 총 수익: %amount%',

    // Shared Header / Footer (header.tpl, footer.tpl, warn.tpl)
    'system_notice' => '시스템 알림',
    'modify_email_settings' => '이메일 수신 설정 변경',

    // Notifications (Notification.php / Cron.php)
    'class_expired_subject' => '%app_name% - 계정 등급이 만료되었습니다',
    'free_traffic_reset_subject' => '%app_name% - 무료 트래픽 초기화 안내',
    'free_traffic_reset_body' => '안녕하세요. 무료 트래픽이 %bandwidth% GB로 초기화되었습니다.',
    'low_traffic_subject' => '%app_name% - 남은 트래픽이 부족합니다',
    'low_traffic_body' => '안녕하세요. 남은 트래픽이 %limit% %unit% 미만으로 확인되었습니다.',
    'new_login_subject' => '%app_name% - 새 로그인 알림',
    'new_login_body' => '회원님의 계정이 %time%에 IP %ip%에서 사용자 패널에 로그인했습니다.',
    'new_sub_subject' => '%app_name% - 새 구독 알림',
    'new_sub_body' => '회원님의 계정이 %time%에 IP %ip%에서 구독을 가져갔습니다.',
    'ticket_replied_subject' => '%app_name% - 문의 티켓 답변 등록',
    'ticket_replied_body' => '안녕하세요. 문의하신 티켓에 새 답변이 등록되었습니다. 확인해 주세요.',
    'new_ticket_subject' => '%app_name% - 새 문의 티켓 등록',
    'new_ticket_body' => '관리자님, 새 문의 티켓이 등록되었습니다. 신속히 처리해 주세요.',
    'ticket_replied_admin_body' => '관리자님, <a href="%ticket_url%">#%ticket_id%</a>번 티켓에 새 답변이 등록되었습니다. 신속히 처리해 주세요.',
    'ticket_replied_user_body' => '안녕하세요. 문의하신 <a href="%ticket_url%">티켓</a>에 새 답변이 등록되었습니다. 확인해 주세요.',
    'ticket_ai_replied_user_body' => '안녕하세요. AI 어시스턴트가 문의하신 <a href="%ticket_url%">티켓</a>에 답변을 남겼습니다. 확인해 주세요.',
    'class_expired_body' => '안녕하세요. 회원님의 계정 등급이 만료되었습니다.',
    'class_expired_reset_body' => '트래픽이 %bandwidth% GB로 초기화되었습니다.',
    'auto_renew_disabled_subject' => '%app_name% - 주문 자동 갱신 비활성화',
    'auto_renew_disabled_off_shelf_body' => '상품 [%product%](주문 #%order_id%)이(가) 판매 중지 또는 단종되어 자동 갱신이 자동으로 비활성화되었습니다. 상점에서 다른 요금제를 선택해 주세요.',
    'auto_renew_failed_subject' => '%app_name% - 주문 자동 갱신 결제 실패',
    'auto_renew_failed_tabp_body' => '상품 [%product%](주문 #%order_id%)이(가) 곧 만료되지만 자동 갱신 결제에 실패했습니다(필요 금액: %price%, 현재 잔액: %balance%). 만료 전에 잔액을 충전하시면 시스템이 다시 시도합니다. 만료 후에는 재시도하지 않습니다.',
    'auto_renew_failed_time_body' => '상품 [%product%](주문 #%order_id%)의 자동 갱신 결제에 실패했습니다(필요 금액: %price%, 현재 잔액: %balance%). 제때 잔액을 충전하시면 시스템이 자동으로 다시 시도합니다.',
    'auto_renew_out_of_stock_subject' => '%app_name% - 주문 자동 갱신 품절 안내',
    'auto_renew_out_of_stock_body' => '상품 [%product%](주문 #%order_id%)이(가) 곧 만료되지만 상품이 일시 품절되어 자동 갱신에 실패했습니다. 재입고를 확인하시거나 상점에서 다른 요금제를 선택해 주세요.',
    'auto_renew_success_subject' => '%app_name% - 주문 자동 갱신 완료',
    'auto_renew_success_body' => '상품 [%product%]이(가) 자동으로 갱신되었습니다. 새 주문 #%order_id%이(가) 생성되었으며 잔액에서 %price%이(가) 차감되었습니다.',
];
