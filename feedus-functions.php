<?php
/**
 * FEEDUS - 통합 기능 파일
 *
 * WPCode에 흩어져 있던 PHP 스니펫을 하나로 통합한 파일입니다.
 * WPCode에서 하나의 PHP 스니펫으로 등록하거나,
 * 테마 functions.php에서 require_once로 불러오세요.
 *
 * 목차:
 * 0. 공통 유틸리티 (전화번호 포맷)
 * 1. 통화 기호 변경 (₩ → 원)
 * 2. 댓글 완전 비활성화
 * 3. 상품 페이지 - Multiple Variations 모드 강제
 * 4. 체크아웃 - 국가 기본값 (한국)
 * 5. 체크아웃 - 영수증 발행 선택 (세금계산서/현금영수증)
 * 6. 체크아웃 - 제목 변경 & 전화번호 포맷
 * 7. 감사 페이지 - 커스터마이징
 * 8. 내 계정 - 주소 전화번호 표시 & 배송 필드 추가
 * 9. 내 계정 - 대시보드 → 주문 리다이렉트
 * 10. 장바구니 - 빈 장바구니 메시지
 * 11. 마우스 대량 할인
 * 12. 장바구니 - 할인/배송료 표시
 * 13. 상품 페이지 - 수량 변경 시 가격 실시간 업데이트
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* ==========================================================================
   0. 공통 유틸리티
   ========================================================================== */

/**
 * +82 국제번호를 한국식 전화번호로 변환
 * 예: +821041224414 → 010-4122-4414
 */
if ( ! function_exists( 'feedus_format_kr_phone' ) ) {
    function feedus_format_kr_phone( $phone ) {
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

        return $phone;
    }
}


/* ==========================================================================
   1. 통화 기호 변경 (₩ → 원)
   ========================================================================== */

add_filter( 'woocommerce_currency_symbol', function( $symbol, $currency ) {
    if ( $currency === 'KRW' ) {
        return '원';
    }
    return $symbol;
}, 10, 2 );

// "원" 기호를 가격 뒤에 표시 (예: 22,000원)
add_filter( 'woocommerce_price_format', function( $format, $currency_pos ) {
    // 항상 숫자 뒤에 통화 기호
    return '%1$s%2$s';
}, 10, 2 );

// 소수점 제거 (원화는 소수점 불필요)
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );


/* ==========================================================================
   2. 댓글 완전 비활성화
   ========================================================================== */

add_action( 'admin_init', function () {
    global $pagenow;

    if ( $pagenow === 'edit-comments.php' ) {
        wp_safe_redirect( admin_url() );
        exit;
    }

    remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );

    foreach ( get_post_types() as $post_type ) {
        if ( post_type_supports( $post_type, 'comments' ) ) {
            remove_post_type_support( $post_type, 'comments' );
            remove_post_type_support( $post_type, 'trackbacks' );
        }
    }
} );

add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

add_action( 'admin_menu', function () {
    remove_menu_page( 'edit-comments.php' );
} );

add_action( 'init', function () {
    if ( is_admin_bar_showing() ) {
        remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
    }
} );


/* ==========================================================================
   3. 상품 페이지 - Multiple Variations CSS 강제
      (JS 부분은 feedus-scripts.js에서 처리)
   ========================================================================== */

add_action( 'wp_head', function () {
    if ( ! is_singular( 'product' ) ) return;
    echo '<style id="feedus-multi-variation-fix">
    .variations_form .wc-variation-mode-selector { display: none !important; }
    .variations_form .single_add_to_cart_button { display: none !important; }
    </style>';
} );


/* ==========================================================================
   4. 체크아웃 - 국가 기본값 (한국, 숨김)
   ========================================================================== */

add_filter( 'woocommerce_checkout_fields', 'feedus_set_default_country' );
function feedus_set_default_country( $fields ) {
    $fields['billing']['billing_country']['default'] = 'KR';
    $fields['billing']['billing_country']['type']    = 'hidden';
    $fields['shipping']['shipping_country']['default'] = 'KR';
    $fields['shipping']['shipping_country']['type']    = 'hidden';
    return $fields;
}


/* ==========================================================================
   5. 체크아웃 - 영수증 발행 선택 (세금계산서/현금영수증)
   ========================================================================== */

// 5-1. UI 출력
add_action( 'woocommerce_review_order_before_payment', 'feedus_receipt_selection_html' );
function feedus_receipt_selection_html() {
    ?>
    <div class="feedus-receipt-box">
        <div class="feedus-receipt-step1">
            <p class="feedus-receipt-main-label">영수증 발행 방식 선택</p>
            <label class="feedus-receipt-radio-label">
                <input type="radio" name="feedus_receipt_type" class="feedus-receipt-radio" value="" checked />
                <span>선택 안 함</span>
            </label>
            <label class="feedus-receipt-radio-label">
                <input type="radio" name="feedus_receipt_type" class="feedus-receipt-radio" value="tax_invoice" />
                <span>세금계산서 발행 요청 (부가세 10% 별도)</span>
            </label>
            <label class="feedus-receipt-radio-label">
                <input type="radio" name="feedus_receipt_type" class="feedus-receipt-radio" value="cash_receipt" />
                <span>현금영수증 신청</span>
            </label>
        </div>
        <div class="feedus-receipt-tax-invoice" style="display:none;">
            <label class="feedus-receipt-confirm-label">
                <input type="checkbox" name="feedus_tax_confirm" id="feedus_tax_confirm" class="feedus-receipt-checkbox" value="1" />
                <span>세금계산서 발행 시 현금영수증은 발행되지 않는 점을 확인했습니다</span>
            </label>
            <div class="feedus-receipt-fields">
                <input type="text" name="feedus_tax_biz_no" id="feedus_tax_biz_no" placeholder="사업자등록번호" class="feedus-receipt-input" />
                <input type="text" name="feedus_tax_biz_name" id="feedus_tax_biz_name" placeholder="상호명" class="feedus-receipt-input" />
                <input type="text" name="feedus_tax_rep_name" id="feedus_tax_rep_name" placeholder="대표자명" class="feedus-receipt-input" />
                <input type="email" name="feedus_tax_email" id="feedus_tax_email" placeholder="이메일" class="feedus-receipt-input" />
            </div>
            <div class="feedus-receipt-notice">
                <p>세금계산서 발행 요청 시 공급가액 기준으로 처리되며, 부가세 10%가 추가됩니다.</p>
                <p>세금계산서와 현금영수증은 중복 발행되지 않습니다.</p>
            </div>
        </div>
        <div class="feedus-receipt-cash" style="display:none;">
            <label class="feedus-receipt-confirm-label">
                <input type="checkbox" name="feedus_cash_confirm" id="feedus_cash_confirm" class="feedus-receipt-checkbox" value="1" />
                <span>소매가 적용 주문에 한해 현금영수증 발행이 가능합니다</span>
            </label>
            <div class="feedus-receipt-cash-fields" style="display:none;">
                <select id="feedus_cash_type" name="feedus_cash_type" class="feedus-receipt-select">
                    <option value="">용도 선택</option>
                    <option value="personal">개인소득공제용</option>
                    <option value="business">사업자지출증빙용</option>
                </select>
                <div class="feedus-receipt-cash-input-wrap" style="display:none;">
                    <input type="text" name="feedus_cash_number" id="feedus_cash_number" placeholder="" class="feedus-receipt-input" />
                </div>
            </div>
            <div class="feedus-receipt-notice">
                <p>도매가 적용 주문은 현금영수증 발행 대상이 아닙니다.</p>
                <p>50마리 이하 소매가 주문만 신청 가능합니다.</p>
            </div>
        </div>
    </div>
    <?php
}

// 5-2. 프론트엔드 JS
add_action( 'wp_footer', 'feedus_receipt_script' );
function feedus_receipt_script() {
    if ( ! is_checkout() ) return;
    $nonce = wp_create_nonce( 'feedus-tax-invoice-nonce' );
    ?>
    <script type="text/javascript">
    (function($) {
        var ajaxUrl = wc_checkout_params.ajax_url;
        var nonce = '<?php echo $nonce; ?>';

        function saveSession(type) {
            $.ajax({
                type: 'POST', url: ajaxUrl,
                data: { action: 'feedus_toggle_tax_invoice', receipt_type: type, security: nonce },
                success: function() { $(document.body).trigger('update_checkout'); }
            });
        }

        $(document.body).on('change', 'input[name="feedus_receipt_type"]', function() {
            var val = $(this).val();
            $('.feedus-receipt-tax-invoice, .feedus-receipt-cash').hide();
            $('.feedus-receipt-cash-fields, .feedus-receipt-cash-input-wrap').hide();
            $('#feedus_tax_confirm, #feedus_cash_confirm').prop('checked', false);
            $('#feedus_cash_type').val('');
            if (val === 'tax_invoice') $('.feedus-receipt-tax-invoice').slideDown(200);
            else if (val === 'cash_receipt') $('.feedus-receipt-cash').slideDown(200);
            saveSession(val);
        });

        $(document.body).on('change', '#feedus_cash_confirm', function() {
            if ($(this).is(':checked')) $('.feedus-receipt-cash-fields').slideDown(200);
            else { $('.feedus-receipt-cash-fields').slideUp(200); $('.feedus-receipt-cash-input-wrap').hide(); $('#feedus_cash_type').val(''); }
        });

        $(document.body).on('change', '#feedus_cash_type', function() {
            var val = $(this).val();
            var $input = $('#feedus_cash_number');
            if (val === 'personal') { $input.attr('placeholder', '휴대폰번호'); $('.feedus-receipt-cash-input-wrap').slideDown(200); }
            else if (val === 'business') { $input.attr('placeholder', '사업자등록번호'); $('.feedus-receipt-cash-input-wrap').slideDown(200); }
            else { $('.feedus-receipt-cash-input-wrap').slideUp(200); }
        });

        $(document.body).on('updated_checkout', function() {
            var $state = $('.feedus-receipt-state');
            if ($state.length && $state.val()) {
                var type = $state.val();
                $('input[name="feedus_receipt_type"][value="' + type + '"]').prop('checked', true);
                if (type === 'tax_invoice') $('.feedus-receipt-tax-invoice').show();
                else if (type === 'cash_receipt') $('.feedus-receipt-cash').show();
            }
        });
    })(jQuery);
    </script>
    <?php
}

// 5-3. AJAX 핸들러
add_action( 'wp_ajax_feedus_toggle_tax_invoice', 'feedus_toggle_tax_invoice' );
add_action( 'wp_ajax_nopriv_feedus_toggle_tax_invoice', 'feedus_toggle_tax_invoice' );
function feedus_toggle_tax_invoice() {
    check_ajax_referer( 'feedus-tax-invoice-nonce', 'security' );
    $type = sanitize_text_field( $_POST['receipt_type'] );
    WC()->session->set( 'feedus_receipt_type', $type );
    wp_send_json_success();
}

// 5-4. 세금계산서 선택 시 10% 부가세 추가
add_action( 'woocommerce_cart_calculate_fees', 'feedus_add_tax_invoice_fee' );
function feedus_add_tax_invoice_fee( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( WC()->session->get( 'feedus_receipt_type' ) === 'tax_invoice' ) {
        $subtotal   = $cart->get_subtotal();
        $tax_amount = $subtotal * 0.10;
        $cart->add_fee( __( '부가세 (세금계산서)', 'feedus' ), $tax_amount, false );
    }
}

// 5-5. 유효성 검사
add_action( 'woocommerce_checkout_process', 'feedus_receipt_validation' );
function feedus_receipt_validation() {
    $type = isset( $_POST['feedus_receipt_type'] ) ? sanitize_text_field( $_POST['feedus_receipt_type'] ) : '';
    if ( $type === 'tax_invoice' ) {
        if ( empty( $_POST['feedus_tax_confirm'] ) ) wc_add_notice( '세금계산서 발행 확인에 동의해 주세요.', 'error' );
        if ( empty( $_POST['feedus_tax_biz_no'] ) )  wc_add_notice( '사업자등록번호를 입력해 주세요.', 'error' );
        if ( empty( $_POST['feedus_tax_biz_name'] ) ) wc_add_notice( '상호명을 입력해 주세요.', 'error' );
        if ( empty( $_POST['feedus_tax_rep_name'] ) ) wc_add_notice( '대표자명을 입력해 주세요.', 'error' );
        if ( empty( $_POST['feedus_tax_email'] ) )   wc_add_notice( '이메일을 입력해 주세요.', 'error' );
    }
    if ( $type === 'cash_receipt' ) {
        if ( empty( $_POST['feedus_cash_confirm'] ) ) wc_add_notice( '현금영수증 발행 확인에 동의해 주세요.', 'error' );
        if ( empty( $_POST['feedus_cash_type'] ) )    wc_add_notice( '현금영수증 용도를 선택해 주세요.', 'error' );
        if ( empty( $_POST['feedus_cash_number'] ) )  wc_add_notice( '휴대폰번호 또는 사업자등록번호를 입력해 주세요.', 'error' );
    }
}

// 5-6. 주문 메타에 영수증 정보 저장
add_action( 'woocommerce_checkout_create_order', 'feedus_save_receipt_meta', 10, 2 );
function feedus_save_receipt_meta( $order, $data ) {
    $type = isset( $_POST['feedus_receipt_type'] ) ? sanitize_text_field( $_POST['feedus_receipt_type'] ) : '';
    $order->update_meta_data( '_feedus_receipt_type', $type );
    if ( $type === 'tax_invoice' ) {
        $order->update_meta_data( '_feedus_tax_biz_no', sanitize_text_field( $_POST['feedus_tax_biz_no'] ?? '' ) );
        $order->update_meta_data( '_feedus_tax_biz_name', sanitize_text_field( $_POST['feedus_tax_biz_name'] ?? '' ) );
        $order->update_meta_data( '_feedus_tax_rep_name', sanitize_text_field( $_POST['feedus_tax_rep_name'] ?? '' ) );
        $order->update_meta_data( '_feedus_tax_email', sanitize_email( $_POST['feedus_tax_email'] ?? '' ) );
    }
    if ( $type === 'cash_receipt' ) {
        $order->update_meta_data( '_feedus_cash_type', sanitize_text_field( $_POST['feedus_cash_type'] ?? '' ) );
        $order->update_meta_data( '_feedus_cash_number', sanitize_text_field( $_POST['feedus_cash_number'] ?? '' ) );
    }
}

// 5-7. 관리자 주문 상세에서 영수증 정보 표시
add_action( 'woocommerce_admin_order_data_after_billing_address', 'feedus_display_receipt_admin', 10, 1 );
function feedus_display_receipt_admin( $order ) {
    $type = $order->get_meta( '_feedus_receipt_type' );
    if ( $type === 'tax_invoice' ) {
        echo '<div style="margin-top:12px;padding:10px;background:#f0faf5;border-left:4px solid #007D51;">';
        echo '<p><strong>세금계산서 발행 요청</strong></p>';
        echo '<p>사업자등록번호: ' . esc_html( $order->get_meta( '_feedus_tax_biz_no' ) ) . '</p>';
        echo '<p>상호명: ' . esc_html( $order->get_meta( '_feedus_tax_biz_name' ) ) . '</p>';
        echo '<p>대표자명: ' . esc_html( $order->get_meta( '_feedus_tax_rep_name' ) ) . '</p>';
        echo '<p>이메일: ' . esc_html( $order->get_meta( '_feedus_tax_email' ) ) . '</p>';
        echo '</div>';
    }
    if ( $type === 'cash_receipt' ) {
        $cash_type_label = $order->get_meta( '_feedus_cash_type' ) === 'personal' ? '개인소득공제용' : '사업자지출증빙용';
        echo '<div style="margin-top:12px;padding:10px;background:#f0faf5;border-left:4px solid #007D51;">';
        echo '<p><strong>현금영수증 신청</strong></p>';
        echo '<p>용도: ' . esc_html( $cash_type_label ) . '</p>';
        echo '<p>번호: ' . esc_html( $order->get_meta( '_feedus_cash_number' ) ) . '</p>';
        echo '</div>';
    }
}

// 5-8. 체크아웃 페이지 갱신 시 선택 상태 유지
add_filter( 'woocommerce_update_order_review_fragments', 'feedus_preserve_receipt_state' );
function feedus_preserve_receipt_state( $fragments ) {
    $type = WC()->session->get( 'feedus_receipt_type' );
    if ( $type ) {
        $fragments['.feedus-receipt-state'] = '<input type="hidden" class="feedus-receipt-state" value="' . esc_attr( $type ) . '" />';
    }
    return $fragments;
}

// 5-9. 세션 초기화
add_action( 'woocommerce_thankyou', 'feedus_clear_receipt_session' );
function feedus_clear_receipt_session() {
    WC()->session->__unset( 'feedus_receipt_type' );
}


/* ==========================================================================
   6. 체크아웃 - 제목 변경 & 전화번호 포맷
   ========================================================================== */

// "청구 상세 내용" → "고객님의 배송지 정보"
add_filter( 'gettext', function ( $translated, $text, $domain ) {
    if ( $domain === 'woocommerce' && $text === 'Billing details' ) {
        return '고객님의 배송지 정보';
    }
    return $translated;
}, 10, 3 );

// 체크아웃 페이지에서 전화번호 한국식 포맷으로 변환
add_action( 'wp_footer', function () {
    if ( ! is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_account_page() ) return;
    ?>
    <script>
    (function() {
        function formatKRPhone(val) {
            if (!val) return val;
            var digits = val.replace(/[^0-9]/g, '');
            if (digits.indexOf('82') === 0 && digits.length >= 11) digits = '0' + digits.substring(2);
            if (digits.length === 11) return digits.substr(0, 3) + '-' + digits.substr(3, 4) + '-' + digits.substr(7, 4);
            else if (digits.length === 10) {
                if (digits.substr(0, 2) === '02') return digits.substr(0, 2) + '-' + digits.substr(2, 4) + '-' + digits.substr(6, 4);
                return digits.substr(0, 3) + '-' + digits.substr(3, 3) + '-' + digits.substr(6, 4);
            }
            return val;
        }
        function initPhoneFormat() {
            var phoneField = document.getElementById('billing_phone');
            if (!phoneField) return;
            if (phoneField.value) phoneField.value = formatKRPhone(phoneField.value);
            phoneField.addEventListener('input', function() {
                var digits = this.value.replace(/[^0-9]/g, '');
                if (digits.length <= 3) this.value = digits;
                else if (digits.length <= 7) this.value = digits.substr(0, 3) + '-' + digits.substr(3);
                else if (digits.length <= 11) this.value = digits.substr(0, 3) + '-' + digits.substr(3, 4) + '-' + digits.substr(7);
                else this.value = digits.substr(0, 3) + '-' + digits.substr(3, 4) + '-' + digits.substr(7, 4);
            });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initPhoneFormat);
        else initPhoneFormat();
        if (typeof jQuery !== 'undefined') jQuery(document.body).on('updated_checkout', initPhoneFormat);
    })();
    </script>
    <?php
} );


/* ==========================================================================
   7. 감사 페이지 (Thank You) - 커스터마이징
   ========================================================================== */

// 기본 성공 메시지 제거
add_filter( 'woocommerce_thankyou_order_received_text', '__return_empty_string' );

// 결제 방법 행 서버 사이드 제거 + 배송 라벨 변경
add_filter( 'woocommerce_get_order_item_totals', function( $total_rows, $order ) {
    if ( isset( $total_rows['payment_method'] ) ) unset( $total_rows['payment_method'] );
    if ( isset( $total_rows['shipping'] ) ) {
        $total_rows['shipping']['label'] = '배송비:';
        $total_rows['shipping']['value'] = preg_replace(
            '/<small\b[^>]*class=["\']shipped_via["\'][^>]*>.*?<\/small>/is',
            '', $total_rows['shipping']['value']
        );
    }
    return $total_rows;
}, 10, 2 );

// 배송 주소에 전화번호 추가 (감사 페이지용)
add_filter( 'woocommerce_order_formatted_shipping_address', function( $address, $order ) {
    if ( $address && is_array( $address ) ) {
        // 체크아웃 페이지에서는 제거, 감사 페이지에서만 추가
        if ( is_wc_endpoint_url( 'order-received' ) ) {
            $phone = $order->get_billing_phone();
            if ( $phone ) $address['phone'] = feedus_format_kr_phone( $phone );
        }
    }
    return $address;
}, 10, 2 );

// 한국 주소 포맷에 {phone} 플레이스홀더 추가
add_filter( 'woocommerce_localisation_address_formats', function( $formats ) {
    if ( isset( $formats['KR'] ) ) {
        if ( strpos( $formats['KR'], '{phone}' ) === false ) {
            $formats['KR'] .= "\n{phone}";
        }
    }
    return $formats;
}, 50 );

// {phone} 치환값 등록
add_filter( 'woocommerce_formatted_address_replacements', function( $replacements, $args ) {
    $phone = isset( $args['phone'] ) ? trim( $args['phone'] ) : '';
    if ( $phone ) {
        $phone = feedus_format_kr_phone( $phone );
    }
    $replacements['{phone}'] = $phone;
    return $replacements;
}, 50, 2 );

// 감사 페이지 DOM 수정 (JS)
add_action( 'wp_footer', 'feedus_thankyou_page_customizations' );
function feedus_thankyou_page_customizations() {
    if ( ! is_wc_endpoint_url( 'order-received' ) ) return;
    ?>
    <script>
    (function() {
        'use strict';
        var el = document.querySelector('.woocommerce-order-overview.woocommerce-thankyou-order-details');
        if (el) el.remove();
        el = document.querySelector('.woocommerce-thankyou-order-received');
        if (el) el.remove();
        el = document.querySelector('.woocommerce-order-details__title');
        if (el) el.textContent = '주문 상품 정보';
        el = document.querySelector('.woocommerce-column--billing-address');
        if (el) el.remove();
        var shippingTitle = document.querySelector('.woocommerce-column--shipping-address .woocommerce-column__title');
        if (shippingTitle) shippingTitle.textContent = '배송지 정보';
        var tfootRows = document.querySelectorAll('.woocommerce-table--order-details tfoot tr');
        tfootRows.forEach(function(row) {
            var th = row.querySelector('th');
            if (!th) return;
            var label = th.textContent.trim().replace(':', '');
            if (label === '결제 방법') { row.classList.add('feedus-hide'); row.style.cssText = 'display:none!important'; }
            if (label === '총계') { row.classList.add('feedus-total-row'); }
            if (label === '배송') {
                th.textContent = '배송비:';
                var td = row.querySelector('td');
                if (td) { var shipped = td.querySelector('.shipped_via'); if (shipped) shipped.remove(); }
            }
        });
    })();
    </script>
    <?php
}


/* ==========================================================================
   8. 내 계정 - 주소 전화번호 표시 & 배송 필드 추가
   ========================================================================== */

// 주소 표시 시 전화번호를 formatted address 데이터에 포함
add_filter( 'woocommerce_my_account_my_address_formatted_address', function ( $address, $customer_id, $address_type ) {
    $phone = get_user_meta( $customer_id, $address_type . '_phone', true );
    if ( ! $phone && $address_type === 'shipping' ) {
        $phone = get_user_meta( $customer_id, 'billing_phone', true );
    }
    if ( $phone ) {
        $address['phone'] = $phone;
    }
    return $address;
}, 20, 3 );

// 배송 주소 편집 폼에서 성(last_name) 필드를 필수 해제 + 전화번호 필드 추가
add_filter( 'woocommerce_shipping_fields', function ( $fields ) {
    if ( isset( $fields['shipping_last_name'] ) ) {
        $fields['shipping_last_name']['required'] = false;
    }
    $fields['shipping_phone'] = array(
        'label'       => '전화번호',
        'required'    => false,
        'type'        => 'tel',
        'class'       => array( 'form-row-wide' ),
        'priority'    => 100,
        'placeholder' => '010-0000-0000',
    );
    return $fields;
} );


/* ==========================================================================
   9. 내 계정 - 대시보드 → 주문 리다이렉트
   ========================================================================== */

add_action( 'template_redirect', function () {
    if ( is_account_page() && is_user_logged_in() && ! is_wc_endpoint_url() ) {
        wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
        exit;
    }
} );


/* ==========================================================================
   10. 장바구니 - 빈 장바구니 메시지
   ========================================================================== */

add_action( 'woocommerce_cart_is_empty', function() {
    echo '<p class="feedus-cart-empty">장바구니가 비어있습니다.</p>';
}, 1 );


/* ==========================================================================
   11. 마우스 대량 할인
   ========================================================================== */

add_action( 'woocommerce_cart_calculate_fees', 'feedus_mouse_bulk_discount' );
function feedus_mouse_bulk_discount( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    $mouse_product_ids = array( 93, 70 );
    $discount_per_mouse = 300;
    $min_quantity = 50;
    $total_mouse_qty = 0;

    foreach ( $cart->get_cart() as $cart_item ) {
        $product_id = $cart_item['product_id'];
        if ( in_array( $product_id, $mouse_product_ids, true ) ) {
            $total_mouse_qty += $cart_item['quantity'];
        }
    }

    if ( $total_mouse_qty >= $min_quantity ) {
        $discount = $total_mouse_qty * $discount_per_mouse;
        $cart->add_fee( '마우스 대량 할인 (' . $total_mouse_qty . '마리)', -$discount );
    }
}


/* ==========================================================================
   12. 장바구니 - 할인/배송료 표시
   ========================================================================== */

add_action( 'woocommerce_cart_totals_after_subtotal', 'feedus_show_cart_fees_and_shipping' );
function feedus_show_cart_fees_and_shipping() {
    $cart = WC()->cart;

    // 배송료 표시
    if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
        ?>
        <tr class="shipping">
            <th>배송료</th>
            <td data-title="배송료">
                <?php
                $packages = WC()->shipping()->get_packages();
                $has_shipping = false;
                foreach ( $packages as $package ) {
                    if ( ! empty( $package['rates'] ) ) {
                        foreach ( $package['rates'] as $rate ) {
                            $has_shipping = true;
                            if ( $rate->cost > 0 ) echo wp_kses_post( wc_price( $rate->cost ) );
                            else echo '무료 배송';
                            break 2;
                        }
                    }
                }
                if ( ! $has_shipping ) echo '배송지를 입력해주세요';
                ?>
            </td>
        </tr>
        <?php
    }

    // 할인(fee) 표시
    foreach ( $cart->get_fees() as $fee ) {
        ?>
        <tr class="fee">
            <th><?php echo esc_html( $fee->name ); ?></th>
            <td data-title="<?php echo esc_attr( $fee->name ); ?>">
                <?php echo wp_kses_post( wc_cart_totals_fee_html( $fee ) ); ?>
            </td>
        </tr>
        <?php
    }
}


/* ==========================================================================
   13. 상품 페이지 - 수량 변경 시 가격 실시간 업데이트
   ========================================================================== */

add_action( 'wp_footer', 'feedus_quantity_price_script' );
function feedus_quantity_price_script() {
    if ( ! is_singular( 'product' ) ) return;
    ?>
    <script>
    jQuery(function($){
        // 옵션(variations) 있는 상품은 제외 — 단순 상품만 적용
        if (!$("body").hasClass("product-type-simple")) return;

        var $priceWrap = $(".brxe-product-price .price");
        if (!$priceWrap.length) return;

        var priceText = $priceWrap.find(".woocommerce-Price-amount").first().text();
        var unitPrice = parseInt(priceText.replace(/[^\d]/g,""),10);
        if (!unitPrice || isNaN(unitPrice)) return;

        // 페이지 로드 시 원본도 "숫자+원" 순서로 통일
        var priceHtml =
            '<span class="woocommerce-Price-amount amount"><bdi>' +
            unitPrice.toLocaleString("ko-KR") +
            '<span class="woocommerce-Price-currencySymbol">원</span>' +
            '</bdi></span>';
        $priceWrap.html(priceHtml);

        function updatePrice(){
            var qty = parseInt($("form.cart input.qty").val(),10) || 1;
            var total = unitPrice * qty;
            var formatted = total.toLocaleString("ko-KR");
            $priceWrap.html(
                '<span class="woocommerce-Price-amount amount"><bdi>' +
                formatted +
                '<span class="woocommerce-Price-currencySymbol">원</span>' +
                '</bdi></span>'
            );
        }

        $(document).on("change input","form.cart input.qty", updatePrice);
        $(document).on("click","form.cart .quantity .action.plus, form.cart .quantity .action.minus", function(){
            setTimeout(updatePrice, 50);
        });
    });
    </script>
    <?php
}
