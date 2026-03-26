<?php
/**
 * Plugin Name: Feedus 세금계산서 발행 체크박스
 * Description: WooCommerce 체크아웃에 세금계산서 발행 체크박스 추가 (선택 시 10% 부가세 가산)
 * Version: 1.0.0
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
            '1.0.0'
        );
    }
}

/**
 * 1. 체크아웃 페이지에 세금계산서 체크박스 출력
 */
add_action( 'woocommerce_review_order_before_payment', 'feedus_tax_invoice_checkbox' );
function feedus_tax_invoice_checkbox() {
    ?>
    <div class="feedus-tax-invoice-box">
        <label class="feedus-tax-invoice-label" for="feedus_tax_invoice">
            <input type="checkbox"
                   id="feedus_tax_invoice"
                   name="feedus_tax_invoice"
                   class="feedus-tax-invoice-checkbox"
                   value="1" />
            <span class="feedus-tax-invoice-text">세금계산서 발행 요청 (부가세 10% 추가)</span>
        </label>
    </div>
    <?php
}

/**
 * 2. 체크박스 AJAX로 세션에 저장 + 주문 요약 갱신
 */
add_action( 'wp_footer', 'feedus_tax_invoice_script' );
function feedus_tax_invoice_script() {
    if ( ! is_checkout() ) {
        return;
    }
    ?>
    <script type="text/javascript">
    (function($) {
        $(document.body).on('change', '#feedus_tax_invoice', function() {
            var checked = $(this).is(':checked') ? 'yes' : 'no';
            $.ajax({
                type: 'POST',
                url: wc_checkout_params.ajax_url,
                data: {
                    action: 'feedus_toggle_tax_invoice',
                    tax_invoice: checked,
                    security: '<?php echo wp_create_nonce( "feedus-tax-invoice-nonce" ); ?>'
                },
                success: function() {
                    $(document.body).trigger('update_checkout');
                }
            });
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * 3. AJAX 핸들러 - 세션에 세금계산서 여부 저장
 */
add_action( 'wp_ajax_feedus_toggle_tax_invoice', 'feedus_toggle_tax_invoice' );
add_action( 'wp_ajax_nopriv_feedus_toggle_tax_invoice', 'feedus_toggle_tax_invoice' );
function feedus_toggle_tax_invoice() {
    check_ajax_referer( 'feedus-tax-invoice-nonce', 'security' );
    WC()->session->set( 'feedus_tax_invoice', sanitize_text_field( $_POST['tax_invoice'] ) );
    wp_send_json_success();
}

/**
 * 4. 세금계산서 선택 시 주문 합계에 10% 수수료 추가
 */
add_action( 'woocommerce_cart_calculate_fees', 'feedus_add_tax_invoice_fee' );
function feedus_add_tax_invoice_fee( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    if ( WC()->session->get( 'feedus_tax_invoice' ) === 'yes' ) {
        $subtotal    = $cart->get_subtotal();
        $tax_amount  = $subtotal * 0.10;
        $cart->add_fee( __( '부가세 (세금계산서)', 'feedus-tax-invoice' ), $tax_amount, false );
    }
}

/**
 * 5. 주문 메타에 세금계산서 발행 여부 저장
 */
add_action( 'woocommerce_checkout_create_order', 'feedus_save_tax_invoice_meta', 10, 2 );
function feedus_save_tax_invoice_meta( $order, $data ) {
    if ( isset( $_POST['feedus_tax_invoice'] ) && $_POST['feedus_tax_invoice'] === '1' ) {
        $order->update_meta_data( '_feedus_tax_invoice', 'yes' );
    } else {
        $order->update_meta_data( '_feedus_tax_invoice', 'no' );
    }
}

/**
 * 6. 관리자 주문 상세에서 세금계산서 발행 여부 표시
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'feedus_display_tax_invoice_admin', 10, 1 );
function feedus_display_tax_invoice_admin( $order ) {
    $tax_invoice = $order->get_meta( '_feedus_tax_invoice' );
    if ( $tax_invoice === 'yes' ) {
        echo '<p><strong>' . esc_html__( '세금계산서 발행:', 'feedus-tax-invoice' ) . '</strong> ';
        echo '<mark class="order-status status-processing" style="background:#007D51;color:#fff;padding:2px 8px;border-radius:4px;">';
        echo esc_html__( '요청됨', 'feedus-tax-invoice' );
        echo '</mark></p>';
    }
}

/**
 * 7. 체크아웃 페이지 갱신 시 체크박스 상태 유지
 */
add_filter( 'woocommerce_update_order_review_fragments', 'feedus_preserve_checkbox_state' );
function feedus_preserve_checkbox_state( $fragments ) {
    if ( WC()->session->get( 'feedus_tax_invoice' ) === 'yes' ) {
        $fragments['.feedus-tax-invoice-checkbox-state'] = '<input type="hidden" class="feedus-tax-invoice-checkbox-state" value="yes" />';
    }
    return $fragments;
}

add_action( 'wp_footer', 'feedus_restore_checkbox_script' );
function feedus_restore_checkbox_script() {
    if ( ! is_checkout() ) {
        return;
    }
    ?>
    <script type="text/javascript">
    (function($) {
        $(document.body).on('updated_checkout', function() {
            if ($('.feedus-tax-invoice-checkbox-state').val() === 'yes') {
                $('#feedus_tax_invoice').prop('checked', true);
            }
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * 8. 세션 초기화 시 값 정리
 */
add_action( 'woocommerce_thankyou', 'feedus_clear_tax_invoice_session' );
function feedus_clear_tax_invoice_session() {
    WC()->session->__unset( 'feedus_tax_invoice' );
}
