<?php
/**
 * FEEDUS - Thank You (Order Received) Page Customizations
 * WPCode > PHP 스니펫 > "어디서나 실행" 으로 추가
 *
 * 수정 사항:
 * 1. "감사합니다. 고객님의 주문이 접수됐습니다." 메시지 제거
 * 2. 주문 요약 (주문번호, 날짜, 이메일, 총계, 결제방법) 리스트 제거
 * 3. "주문 세부 사항" → "주문 상품 정보" 제목 변경
 * 4. 청구 주소 영역 삭제
 * 5. "배송 주소" → "배송지 정보" 제목 변경
 * 6. 배송지 정보 <address> 안에 전화번호 추가 (한국식 포맷: 010-xxxx-xxxx)
 */

// ──────────────────────────────────────────────
// 1. 기본 성공 메시지 제거
// ──────────────────────────────────────────────
add_filter( 'woocommerce_thankyou_order_received_text', '__return_empty_string' );

// ──────────────────────────────────────────────
// 6. 배송 주소 포맷에 전화번호 필드 추가 (서버 사이드)
//    <address> 태그 안에 직접 출력됨
// ──────────────────────────────────────────────

// 6-a. 배송 주소 데이터에 billing_phone 주입
add_filter( 'woocommerce_order_formatted_shipping_address', function( $address, $order ) {
    if ( $address && is_array( $address ) ) {
        $phone = $order->get_billing_phone();
        if ( $phone ) {
            $address['phone'] = feedus_format_kr_phone( $phone );
        }
    }
    return $address;
}, 10, 2 );

// 6-b. 한국 주소 포맷에 {phone} 플레이스홀더 추가
add_filter( 'woocommerce_localisation_address_formats', function( $formats ) {
    if ( isset( $formats['KR'] ) ) {
        $formats['KR'] .= "\n{phone}";
    } else {
        $formats['default'] .= "\n{phone}";
    }
    return $formats;
} );

// 6-c. {phone} 치환값 등록
add_filter( 'woocommerce_formatted_address_replacements', function( $replacements, $args ) {
    $replacements['{phone}'] = isset( $args['phone'] ) ? $args['phone'] : '';
    return $replacements;
}, 10, 2 );

/**
 * +82 국제번호를 한국식 전화번호로 변환
 * 예: +821041224414 → 010-4122-4414
 */
function feedus_format_kr_phone( $phone ) {
    // 숫자만 추출
    $digits = preg_replace( '/[^0-9]/', '', $phone );

    // +82 국제번호 → 0으로 변환
    if ( strpos( $digits, '82' ) === 0 && strlen( $digits ) > 9 ) {
        $digits = '0' . substr( $digits, 2 );
    }

    // 010-xxxx-xxxx (11자리 휴대폰)
    if ( strlen( $digits ) === 11 && strpos( $digits, '01' ) === 0 ) {
        return substr( $digits, 0, 3 ) . '-' . substr( $digits, 3, 4 ) . '-' . substr( $digits, 7 );
    }

    // 02-xxxx-xxxx (서울, 10자리)
    if ( strlen( $digits ) === 10 && strpos( $digits, '02' ) === 0 ) {
        return substr( $digits, 0, 2 ) . '-' . substr( $digits, 2, 4 ) . '-' . substr( $digits, 6 );
    }

    // 0xx-xxx-xxxx (지역번호, 10자리)
    if ( strlen( $digits ) === 10 ) {
        return substr( $digits, 0, 3 ) . '-' . substr( $digits, 3, 3 ) . '-' . substr( $digits, 6 );
    }

    // 변환 불가 시 원본 반환
    return $phone;
}

// ──────────────────────────────────────────────
// 2~5. 나머지 DOM 수정 (JS)
// ──────────────────────────────────────────────
add_action( 'wp_footer', 'feedus_thankyou_page_customizations' );
function feedus_thankyou_page_customizations() {
    if ( ! is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    ?>
    <script>
    (function() {
        'use strict';

        // 2. 주문 요약 리스트 제거
        var el = document.querySelector('.woocommerce-order-overview.woocommerce-thankyou-order-details');
        if (el) el.remove();

        // 1. 빈 notice 요소 제거
        el = document.querySelector('.woocommerce-thankyou-order-received');
        if (el) el.remove();

        // 3. "주문 세부 사항" → "주문 상품 정보"
        el = document.querySelector('.woocommerce-order-details__title');
        if (el) el.textContent = '주문 상품 정보';

        // 4. 청구 주소 영역 삭제
        el = document.querySelector('.woocommerce-column--billing-address');
        if (el) el.remove();

        // 5. "배송 주소" → "배송지 정보"
        var shippingTitle = document.querySelector('.woocommerce-column--shipping-address .woocommerce-column__title');
        if (shippingTitle) shippingTitle.textContent = '배송지 정보';

        // 7. tfoot 결제 방법 행 삭제 + 배송 행 수정
        var tfootRows = document.querySelectorAll('.woocommerce-table--order-details tfoot tr');
        tfootRows.forEach(function(row) {
            var th = row.querySelector('th');
            if (!th) return;
            var label = th.textContent.trim().replace(':', '');

            if (label === '결제 방법') {
                row.remove();
            }

            if (label === '배송') {
                th.textContent = '배송비:';
                var td = row.querySelector('td');
                if (td) {
                    var shipped = td.querySelector('.shipped_via');
                    if (shipped) shipped.remove();
                }
            }
        });
    })();
    </script>
    <?php
}
