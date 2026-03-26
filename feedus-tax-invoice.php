<?php
/**
 * Plugin Name: Feedus 영수증 발행 선택
 * Description: WooCommerce 체크아웃에 세금계산서/현금영수증 선택 기능 (세금계산서 선택 시 10% 부가세 가산)
 * Version: 2.0.0
 * Author: Feedus
 * Text Domain: feedus-tax-invoice
 *
 * 사용법: 이 파일을 wp-content/plugins/ 에 넣고 워드프레스 관리자에서 활성화
 * 또는 테마 functions.php에 require_once 로 불러오세요.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 0. 플러그인 CSS 로드
 */
add_action( 'wp_enqueue_scripts', 'feedus_tax_invoice_styles' );
function feedus_tax_invoice_styles() {
    if ( is_checkout() ) {
        wp_enqueue_style(
            'feedus-tax-invoice',
            plugin_dir_url( __FILE__ ) . 'feedus-tax-invoice.css',
            array(),
            '2.0.0'
        );
    }
}

/**
 * 1. 체크아웃 페이지에 영수증 발행 선택 UI 출력
 */
add_action( 'woocommerce_review_order_before_payment', 'feedus_receipt_selection_html' );
function feedus_receipt_selection_html() {
    ?>
    <div class="feedus-receipt-box">

        <!-- 1단계: 라디오 버튼 선택 -->
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

        <!-- 2단계-A: 세금계산서 -->
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

        <!-- 2단계-B: 현금영수증 -->
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

/**
 * 2. 프론트엔드 JS - UI 토글 + AJAX 세션 저장
 */
add_action( 'wp_footer', 'feedus_receipt_script' );
function feedus_receipt_script() {
    if ( ! is_checkout() ) {
        return;
    }
    $nonce = wp_create_nonce( 'feedus-tax-invoice-nonce' );
    ?>
    <script type="text/javascript">
    (function($) {
        var ajaxUrl = wc_checkout_params.ajax_url;
        var nonce = '<?php echo $nonce; ?>';

        function saveSession(type) {
            $.ajax({
                type: 'POST',
                url: ajaxUrl,
                data: {
                    action: 'feedus_toggle_tax_invoice',
                    receipt_type: type,
                    security: nonce
                },
                success: function() {
                    $(document.body).trigger('update_checkout');
                }
            });
        }

        // 1단계: 라디오 버튼 변경
        $(document.body).on('change', 'input[name="feedus_receipt_type"]', function() {
            var val = $(this).val();

            $('.feedus-receipt-tax-invoice, .feedus-receipt-cash').hide();
            $('.feedus-receipt-cash-fields, .feedus-receipt-cash-input-wrap').hide();
            $('#feedus_tax_confirm, #feedus_cash_confirm').prop('checked', false);
            $('#feedus_cash_type').val('');

            if (val === 'tax_invoice') {
                $('.feedus-receipt-tax-invoice').slideDown(200);
            } else if (val === 'cash_receipt') {
                $('.feedus-receipt-cash').slideDown(200);
            }

            saveSession(val);
        });

        // 2단계-B: 현금영수증 확인 체크 시 하위 필드 표시
        $(document.body).on('change', '#feedus_cash_confirm', function() {
            if ($(this).is(':checked')) {
                $('.feedus-receipt-cash-fields').slideDown(200);
            } else {
                $('.feedus-receipt-cash-fields').slideUp(200);
                $('.feedus-receipt-cash-input-wrap').hide();
                $('#feedus_cash_type').val('');
            }
        });

        // 2단계-B: 용도 선택 시 입력칸 표시
        $(document.body).on('change', '#feedus_cash_type', function() {
            var val = $(this).val();
            var $input = $('#feedus_cash_number');
            if (val === 'personal') {
                $input.attr('placeholder', '휴대폰번호');
                $('.feedus-receipt-cash-input-wrap').slideDown(200);
            } else if (val === 'business') {
                $input.attr('placeholder', '사업자등록번호');
                $('.feedus-receipt-cash-input-wrap').slideDown(200);
            } else {
                $('.feedus-receipt-cash-input-wrap').slideUp(200);
            }
        });

        // 체크아웃 갱신 후 상태 복원
        $(document.body).on('updated_checkout', function() {
            var $state = $('.feedus-receipt-state');
            if ($state.length && $state.val()) {
                var type = $state.val();
                $('input[name="feedus_receipt_type"][value="' + type + '"]').prop('checked', true);
                if (type === 'tax_invoice') {
                    $('.feedus-receipt-tax-invoice').show();
                } else if (type === 'cash_receipt') {
                    $('.feedus-receipt-cash').show();
                }
            }
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * 3. AJAX 핸들러 - 세션에 영수증 타입 저장
 */
add_action( 'wp_ajax_feedus_toggle_tax_invoice', 'feedus_toggle_tax_invoice' );
add_action( 'wp_ajax_nopriv_feedus_toggle_tax_invoice', 'feedus_toggle_tax_invoice' );
function feedus_toggle_tax_invoice() {
    check_ajax_referer( 'feedus-tax-invoice-nonce', 'security' );
    $type = sanitize_text_field( $_POST['receipt_type'] );
    WC()->session->set( 'feedus_receipt_type', $type );
    wp_send_json_success();
}

/**
 * 4. 세금계산서 선택 시 주문 합계에 10% 부가세 추가
 */
add_action( 'woocommerce_cart_calculate_fees', 'feedus_add_tax_invoice_fee' );
function feedus_add_tax_invoice_fee( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    if ( WC()->session->get( 'feedus_receipt_type' ) === 'tax_invoice' ) {
        $subtotal   = $cart->get_subtotal();
        $tax_amount = $subtotal * 0.10;
        $cart->add_fee( __( '부가세 (세금계산서)', 'feedus-tax-invoice' ), $tax_amount, false );
    }
}

/**
 * 5. 체크아웃 유효성 검사
 */
add_action( 'woocommerce_checkout_process', 'feedus_receipt_validation' );
function feedus_receipt_validation() {
    $type = isset( $_POST['feedus_receipt_type'] ) ? sanitize_text_field( $_POST['feedus_receipt_type'] ) : '';

    if ( $type === 'tax_invoice' ) {
        if ( empty( $_POST['feedus_tax_confirm'] ) ) {
            wc_add_notice( '세금계산서 발행 확인에 동의해 주세요.', 'error' );
        }
        if ( empty( $_POST['feedus_tax_biz_no'] ) ) {
            wc_add_notice( '사업자등록번호를 입력해 주세요.', 'error' );
        }
        if ( empty( $_POST['feedus_tax_biz_name'] ) ) {
            wc_add_notice( '상호명을 입력해 주세요.', 'error' );
        }
        if ( empty( $_POST['feedus_tax_rep_name'] ) ) {
            wc_add_notice( '대표자명을 입력해 주세요.', 'error' );
        }
        if ( empty( $_POST['feedus_tax_email'] ) ) {
            wc_add_notice( '이메일을 입력해 주세요.', 'error' );
        }
    }

    if ( $type === 'cash_receipt' ) {
        if ( empty( $_POST['feedus_cash_confirm'] ) ) {
            wc_add_notice( '현금영수증 발행 확인에 동의해 주세요.', 'error' );
        }
        if ( empty( $_POST['feedus_cash_type'] ) ) {
            wc_add_notice( '현금영수증 용도를 선택해 주세요.', 'error' );
        }
        if ( empty( $_POST['feedus_cash_number'] ) ) {
            wc_add_notice( '휴대폰번호 또는 사업자등록번호를 입력해 주세요.', 'error' );
        }
    }
}

/**
 * 6. 주문 메타에 영수증 정보 저장
 */
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

/**
 * 7. 관리자 주문 상세에서 영수증 정보 표시
 */
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

/**
 * 8. 체크아웃 페이지 갱신 시 선택 상태 유지
 */
add_filter( 'woocommerce_update_order_review_fragments', 'feedus_preserve_receipt_state' );
function feedus_preserve_receipt_state( $fragments ) {
    $type = WC()->session->get( 'feedus_receipt_type' );
    if ( $type ) {
        $fragments['.feedus-receipt-state'] = '<input type="hidden" class="feedus-receipt-state" value="' . esc_attr( $type ) . '" />';
    }
    return $fragments;
}

/**
 * 9. 세션 초기화
 */
add_action( 'woocommerce_thankyou', 'feedus_clear_receipt_session' );
function feedus_clear_receipt_session() {
    WC()->session->__unset( 'feedus_receipt_type' );
}
