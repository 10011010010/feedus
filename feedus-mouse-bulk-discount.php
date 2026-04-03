/**
 * FEEDUS - 마우스 대량 할인
 * 마우스(라이브+냉동) 총 수량이 50마리 이상이면 마우스 1마리당 -300원 할인
 *
 * WPCode > PHP 스니펫 > "Run Everywhere" 로 추가
 * ⚠️ 코드 타입: PHP Snippet (JavaScript 아님!)
 */

add_action( 'woocommerce_cart_calculate_fees', 'feedus_mouse_bulk_discount' );

function feedus_mouse_bulk_discount( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    // 마우스 상품 ID (라이브: 93, 냉동: 70)
    $mouse_product_ids = array( 93, 70 );
    $discount_per_mouse = 300; // 원
    $min_quantity = 50;         // 50마리부터 적용

    $total_mouse_qty = 0;

    foreach ( $cart->get_cart() as $cart_item ) {
        $product_id = $cart_item['product_id'];
        if ( in_array( $product_id, $mouse_product_ids, true ) ) {
            $total_mouse_qty += $cart_item['quantity'];
        }
    }

    if ( $total_mouse_qty >= $min_quantity ) {
        $discount = $total_mouse_qty * $discount_per_mouse; // 총 할인액
        $cart->add_fee( '마우스 대량 할인 (' . $total_mouse_qty . '마리)', -$discount );
    }
}
