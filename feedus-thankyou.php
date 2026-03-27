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
 * 6. 배송지 정보에 전화번호 추가 (한국식 포맷: 010-xxxx-xxxx)
 */

// 1. "감사합니다. 고객님의 주문이 접수됐습니다." 기본 메시지 제거
add_filter( 'woocommerce_thankyou_order_received_text', '__return_empty_string' );

// 2. 주문 요약 리스트 제거 (주문번호, 날짜, 이메일, 총계, 결제방법)
// 3. "주문 세부 사항" → "주문 상품 정보"
// 4. 청구 주소 삭제
// 5. "배송 주소" → "배송지 정보"
// 6. 배송지 정보에 전화번호 추가 (KR 포맷)
add_action( 'wp_footer', 'feedus_thankyou_page_customizations' );
function feedus_thankyou_page_customizations() {
    // 감사 페이지에서만 실행
    if ( ! is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    ?>
    <script>
    (function() {
        'use strict';

        // 2. 주문 요약 리스트 제거
        var orderOverview = document.querySelector('.woocommerce-order-overview.woocommerce-thankyou-order-details');
        if (orderOverview) {
            orderOverview.remove();
        }

        // 1. 빈 notice 요소도 완전히 제거
        var notice = document.querySelector('.woocommerce-thankyou-order-received');
        if (notice) {
            notice.remove();
        }

        // 3. "주문 세부 사항" → "주문 상품 정보"
        var orderDetailsTitle = document.querySelector('.woocommerce-order-details__title');
        if (orderDetailsTitle) {
            orderDetailsTitle.textContent = '주문 상품 정보';
        }

        // 4. 청구 주소 영역 삭제
        var billingCol = document.querySelector('.woocommerce-column--billing-address');
        if (billingCol) {
            billingCol.remove();
        }

        // 5. "배송 주소" → "배송지 정보"
        var shippingCol = document.querySelector('.woocommerce-column--shipping-address');
        if (shippingCol) {
            var shippingTitle = shippingCol.querySelector('.woocommerce-column__title');
            if (shippingTitle) {
                shippingTitle.textContent = '배송지 정보';
            }

            // 6. 배송지 정보에 전화번호 추가 (KR 포맷)
            // 청구 주소에 있던 전화번호를 배송지로 이동
            var phoneEl = document.querySelector('.woocommerce-customer-details--phone');
            if (phoneEl) {
                var phoneText = phoneEl.textContent.trim();
                var formatted = formatKRPhone(phoneText);

                var newPhoneP = document.createElement('p');
                newPhoneP.className = 'woocommerce-customer-details--phone';
                newPhoneP.textContent = formatted;

                var shippingAddress = shippingCol.querySelector('address');
                if (shippingAddress) {
                    shippingAddress.appendChild(newPhoneP);
                }
            }
        }

        /**
         * 국제 전화번호(+82...)를 한국식(010-xxxx-xxxx)으로 변환
         * 이미 0으로 시작하면 하이픈만 추가
         */
        function formatKRPhone(phone) {
            // 숫자만 추출
            var digits = phone.replace(/[^0-9]/g, '');

            // +82 국제번호 → 0 으로 변환
            if (digits.indexOf('82') === 0 && digits.length > 9) {
                digits = '0' + digits.substring(2);
            }

            // 010-xxxx-xxxx (11자리 휴대폰)
            if (digits.length === 11 && digits.indexOf('01') === 0) {
                return digits.substring(0, 3) + '-' + digits.substring(3, 7) + '-' + digits.substring(7);
            }

            // 02-xxxx-xxxx (서울 지역번호, 10자리)
            if (digits.length === 10 && digits.indexOf('02') === 0) {
                return digits.substring(0, 2) + '-' + digits.substring(2, 6) + '-' + digits.substring(6);
            }

            // 0xx-xxx-xxxx (지역번호 10자리)
            if (digits.length === 10) {
                return digits.substring(0, 3) + '-' + digits.substring(3, 6) + '-' + digits.substring(6);
            }

            // 0xx-xxxx-xxxx (지역번호 11자리)
            if (digits.length === 11) {
                return digits.substring(0, 3) + '-' + digits.substring(3, 7) + '-' + digits.substring(7);
            }

            // 변환 불가 시 원본 반환
            return phone;
        }
    })();
    </script>
    <?php
}
