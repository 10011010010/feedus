<?php
/**
 * Plugin Name: Feedus 국가 필드 기본값 설정
 * Description: WooCommerce 체크아웃에서 billing_country를 숨기고 기본값 KR로 설정
 * Version: 1.0.0
 * Author: Feedus
 * Text Domain: feedus-country-default
 *
 * 사용법: wp-content/plugins/ 에 넣고 활성화
 * 또는 WPCode 스니펫으로 PHP 코드 부분만 등록
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'woocommerce_checkout_fields', 'feedus_set_default_country' );
function feedus_set_default_country( $fields ) {
    $fields['billing']['billing_country']['default'] = 'KR';
    $fields['billing']['billing_country']['type']    = 'hidden';

    $fields['shipping']['shipping_country']['default'] = 'KR';
    $fields['shipping']['shipping_country']['type']    = 'hidden';

    return $fields;
}
