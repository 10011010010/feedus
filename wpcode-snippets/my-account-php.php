/**
 * FEEDUS - My Account 페이지 커스터마이징
 * WPCode 스니펫으로 추가 (위치: 어디서나 실행)
 * WPCode snippet #358
 *
 * 1. 대시보드 메뉴 삭제
 * 2. 다운로드 메뉴 삭제
 * 3. My Account 첫 화면에 주문 목록 표시 (대시보드 → 주문)
 * 4. 대시보드 환영 메시지 삭제
 * 5. 주소 페이지: 청구 주소 숨기고 배송 주소만 표시
 * 6. 주소 페이지 안내 문구 변경
 * 7. 계정 정보 페이지: "비밀번호 변경" legend 삭제 + fieldset border 제거
 * 8. 계정 정보 페이지: 성(last_name) 필드 숨기기 + 필수 해제
 */

// 계정 정보 저장 시 성(last_name) 필수 해제
add_filter( 'woocommerce_save_account_details_required_fields', 'feedus_remove_last_name_required' );

function feedus_remove_last_name_required( $fields ) {
	unset( $fields['account_last_name'] );
	return $fields;
}

// 대시보드, 다운로드 메뉴 삭제
add_filter( 'woocommerce_account_menu_items', 'feedus_remove_my_account_links' );

function feedus_remove_my_account_links( $menu_links ) {
	unset( $menu_links['dashboard'] );
	unset( $menu_links['downloads'] );
	return $menu_links;
}

// 대시보드 환영 메시지("안녕하세요, OOO 님" + 안내 문구) 삭제 + 주문 목록으로 대체
add_action( 'init', 'feedus_replace_dashboard_with_orders' );

function feedus_replace_dashboard_with_orders() {
	remove_action( 'woocommerce_account_dashboard', 'woocommerce_account_dashboard_output', 10 );
	add_action( 'woocommerce_account_dashboard', 'feedus_show_orders_on_dashboard' );
}

// 주소 페이지: 청구 주소 숨기고 배송 주소만 표시
add_filter( 'woocommerce_my_account_get_addresses', 'feedus_only_shipping_address' );

function feedus_only_shipping_address( $addresses ) {
	unset( $addresses['billing'] );
	return $addresses;
}

// 주소 페이지 안내 문구 변경
add_filter( 'woocommerce_my_account_edit_address_title', 'feedus_address_page_title', 10, 2 );

function feedus_address_page_title( $title, $load_address ) {
	if ( 'shipping' === $load_address ) {
		return '배송 주소 수정';
	}
	return $title;
}

// 주소 페이지 상단 안내 문구("결제 페이지에서 기본으로 사용될 주소입니다") 변경
add_filter( 'gettext', 'feedus_address_description_text', 20, 3 );

function feedus_address_description_text( $translated, $text, $domain ) {
	if ( 'woocommerce' === $domain && 'The following addresses will be used on the checkout page by default.' === $text ) {
		return '배송 시 사용될 기본 주소입니다.';
	}
	return $translated;
}

// My Account 주소 편집 페이지에 카카오 주소 검색 적용
add_action( 'wp_footer', 'feedus_myaccount_kakao_address_script' );

function feedus_myaccount_kakao_address_script() {
	if ( ! is_account_page() ) {
		return;
	}
	?>
	<style>
		.woocommerce-EditAccountForm fieldset {
			border: none !important;
			padding: 0 !important;
			margin: 0 !important;
		}
		.woocommerce-EditAccountForm fieldset legend {
			display: none !important;
		}
		#account_display_name_description {
			display: none !important;
		}
		#account_last_name_field,
		.woocommerce-form-row--last:has(#account_last_name) {
			display: none !important;
		}
	</style>
	<script>
	(function($) {
		'use strict';

		function openPostcodePopup(prefix) {
			if (typeof daum === 'undefined' || typeof daum.Postcode === 'undefined') {
				alert('주소 검색 서비스를 불러오는 중입니다. 잠시 후 다시 시도해 주세요.');
				return;
			}

			new daum.Postcode({
				oncomplete: function(data) {
					var roadAddr = data.roadAddress;
					var jibunAddr = data.jibunAddress;
					var addr = roadAddr || jibunAddr;

					var extraAddr = '';
					if (data.addressType === 'R') {
						if (data.bname && /[동|로|가]$/g.test(data.bname)) {
							extraAddr += data.bname;
						}
						if (data.buildingName) {
							extraAddr += (extraAddr ? ', ' : '') + data.buildingName;
						}
						if (extraAddr) {
							extraAddr = ' (' + extraAddr + ')';
						}
					}

					$('#' + prefix + '_postcode').val(data.zonecode).trigger('change');
					$('#' + prefix + '_address_1').val(addr + extraAddr).trigger('change');
					$('#' + prefix + '_city').val(data.sido).trigger('change');
					$('#' + prefix + '_address_2').val('').focus();
				},
				width: '100%',
				height: '100%'
			}).open();
		}

		function setupAddressFields(prefix) {
			var $addressField = $('#' + prefix + '_address_1_field');
			if (!$addressField.length) return;
			if ($addressField.find('.feedus-address-search-btn').length) return;

			var $btn = $(
				'<button type="button" class="feedus-address-search-btn">' +
					'<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
					'<circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>' +
					'</svg>' +
					'<span>주소 검색</span>' +
				'</button>'
			);

			$btn.on('click', function(e) {
				e.preventDefault();
				openPostcodePopup(prefix);
			});

			$addressField.find('label').after($btn);

			$('#' + prefix + '_postcode').prop('readonly', true);
			$('#' + prefix + '_address_1').prop('readonly', true);
			$('#' + prefix + '_city').prop('readonly', true);

			$('#' + prefix + '_postcode, #' + prefix + '_address_1, #' + prefix + '_city').on('click focus', function(e) {
				if (!$(this).val()) {
					e.preventDefault();
					openPostcodePopup(prefix);
				}
			});
		}

		$(document).ready(function() {
			if ($('form.woocommerce-EditAccountForm, form.edit-account, .woocommerce-address-fields').length) {
				setupAddressFields('shipping');
				setupAddressFields('billing');
			}
		});
	})(jQuery);
	</script>
	<?php
}

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
