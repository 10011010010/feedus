/**
 * FEEDUS - 장바구니 합계에 할인/배송료 표시
 * Bricks Builder Cart Totals에서 fee, shipping 행이 누락되는 문제 수정
 *
 * WPCode > PHP Snippet > "Run Everywhere"
 */

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
                            if ( $rate->cost > 0 ) {
                                echo wp_kses_post( wc_price( $rate->cost ) );
                            } else {
                                echo '무료 배송';
                            }
                            break 2;
                        }
                    }
                }
                if ( ! $has_shipping ) {
                    echo '배송지를 입력해주세요';
                }
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
