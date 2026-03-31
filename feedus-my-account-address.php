<?php
/**
 * FEEDUS - 배송 주소 전화번호 표시 + 한국식 포맷
 * WPCode snippet #358에 추가하거나 별도 PHP 스니펫으로 등록
 * 위치: 어디서나 실행 (Everywhere)
 *
 * 기능:
 * 1. 내 계정 > 주소 페이지에서 배송 주소에 전화번호 표시
 * 2. 전화번호를 한국식(010-0000-0000) 포맷으로 자동 변환
 */

// 1. 배송 주소 표시 시 전화번호를 formatted address 데이터에 포함
add_filter('woocommerce_my_account_my_address_formatted_address', function ($address, $customer_id, $address_type) {
    $phone = get_user_meta($customer_id, $address_type . '_phone', true);
    if ($phone) {
        $address['phone'] = $phone;
    }
    return $address;
}, 20, 3);

// 2. 주소 포맷 문자열에 {phone} 추가
add_filter('woocommerce_localisation_address_formats', function ($formats) {
    foreach ($formats as $country => &$format) {
        if (strpos($format, '{phone}') === false) {
            $format .= "\n{phone}";
        }
    }
    return $formats;
});

// 3. {phone} 치환자를 실제 값으로 교체 (한국식 포맷 적용)
add_filter('woocommerce_formatted_address_replacements', function ($replacements, $args) {
    $phone = isset($args['phone']) ? trim($args['phone']) : '';

    if ($phone) {
        // 이미 포맷된 번호가 아닌 경우 한국식으로 변환
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) === 11) {
            // 010-1234-5678
            $phone = substr($digits, 0, 3) . '-' . substr($digits, 3, 4) . '-' . substr($digits, 7, 4);
        } elseif (strlen($digits) === 10) {
            // 02-123-4567 또는 031-123-4567
            if (substr($digits, 0, 2) === '02') {
                $phone = substr($digits, 0, 2) . '-' . substr($digits, 2, 4) . '-' . substr($digits, 6, 4);
            } else {
                $phone = substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
            }
        }
    }

    $replacements['{phone}'] = $phone;
    return $replacements;
}, 10, 2);
