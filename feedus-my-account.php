<?php
/**
 * FEEDUS - My Account 페이지 커스터마이징
 * WPCode 스니펫으로 추가 (위치: 어디서나 실행)
 *
 * 1. 대시보드 메뉴 삭제
 * 2. 다운로드 메뉴 삭제
 * 3. My Account 첫 화면에 주문 목록 표시 (대시보드 → 주문)
 */

// 대시보드, 다운로드 메뉴 삭제
add_filter( 'woocommerce_account_menu_items', 'feedus_remove_my_account_links' );

function feedus_remove_my_account_links( $menu_links ) {
	unset( $menu_links['dashboard'] );
	unset( $menu_links['downloads'] );
	return $menu_links;
}

// My Account 첫 화면(대시보드)을 주문 목록으로 대체
add_action( 'woocommerce_account_dashboard', 'feedus_show_orders_on_dashboard' );

function feedus_show_orders_on_dashboard() {
	wc_get_template(
		'myaccount/orders.php',
		array(
			'current_page'    => 1,
			'customer_orders' => wc_get_orders(
				array(
					'customer' => get_current_user_id(),
					'page'     => 1,
					'paginate' => true,
				)
			),
			'has_orders'      => wc_get_customer_order_count( get_current_user_id() ) > 0,
		)
	);
}
