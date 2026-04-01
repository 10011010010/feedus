/**
 * FEEDUS - My Account 대시보드 → 주문 페이지 리다이렉트
 * WPCode snippet (PHP 스니펫 - 어디서나 실행)
 *
 * /my-account/ 대시보드 진입 시 자동으로 /my-account/orders/ 로 이동
 */
add_action('template_redirect', function () {
    if (is_account_page() && is_user_logged_in() && !is_wc_endpoint_url()) {
        wp_safe_redirect(wc_get_account_endpoint_url('orders'));
        exit;
    }
});
