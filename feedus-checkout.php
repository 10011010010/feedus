/**
 * FEEDUS - Checkout 페이지 커스터마이징
 * WPCode snippet (PHP 스니펫 - 어디서나 실행)
 *
 * 1. "청구 상세 내용" → "고객님의 배송지 정보" 제목 변경
 * 2. 전화번호 국제번호(+82) → 한국식(010-) 포맷 변환
 * 3. 주소 영역 전화번호 중복 제거
 */

// "청구 상세 내용" 제목 변경
add_filter('gettext', function ($translated, $text, $domain) {
    if ($domain === 'woocommerce' && $text === 'Billing details') {
        return '고객님의 배송지 정보';
    }
    return $translated;
}, 10, 3);

// 주소 포맷에서 전화번호 플레이스홀더 제거
add_filter('woocommerce_localisation_address_formats', function ($formats) {
    foreach ($formats as $country => &$format) {
        $format = str_replace(array('{phone}', '{billing_phone}', '{shipping_phone}'), '', $format);
        $format = preg_replace('/\n{2,}/', "\n", trim($format));
    }
    return $formats;
}, 999);

// 주소 치환값에서 전화번호 값을 빈 문자열로 강제 설정
add_filter('woocommerce_formatted_address_replacements', function ($replacements) {
    $phone_keys = array('{phone}', '{billing_phone}', '{shipping_phone}', '{phone_1}', '{phone_2}');
    foreach ($phone_keys as $key) {
        if (isset($replacements[$key])) {
            $replacements[$key] = '';
        }
    }
    return $replacements;
}, 999);

// 주문 상세의 formatted billing/shipping address에서 phone 제거
add_filter('woocommerce_order_formatted_billing_address', function ($address) {
    unset($address['phone'], $address['billing_phone']);
    return $address;
}, 999);

add_filter('woocommerce_order_formatted_shipping_address', function ($address) {
    unset($address['phone'], $address['shipping_phone']);
    return $address;
}, 999);

// 고객 계정 페이지 주소에서도 phone 제거
add_filter('woocommerce_my_account_my_address_formatted_address', function ($address, $customer_id, $address_type) {
    unset($address['phone'], $address['billing_phone'], $address['shipping_phone']);
    return $address;
}, 999, 3);

// 체크아웃 페이지에서 전화번호 한국식 포맷으로 변환
add_action('wp_footer', function () {
    if (!is_checkout() && !is_wc_endpoint_url('order-received') && !is_account_page()) return;
    ?>
    <script>
    (function() {
        function formatKRPhone(val) {
            if (!val) return val;
            // +82 국제번호 → 0으로 변환
            var digits = val.replace(/[^0-9]/g, '');
            if (digits.indexOf('82') === 0 && digits.length >= 11) {
                digits = '0' + digits.substring(2);
            }
            // 하이픈 포맷
            if (digits.length === 11) {
                return digits.substr(0, 3) + '-' + digits.substr(3, 4) + '-' + digits.substr(7, 4);
            } else if (digits.length === 10) {
                if (digits.substr(0, 2) === '02') {
                    return digits.substr(0, 2) + '-' + digits.substr(2, 4) + '-' + digits.substr(6, 4);
                }
                return digits.substr(0, 3) + '-' + digits.substr(3, 3) + '-' + digits.substr(6, 4);
            }
            return val;
        }

        function initPhoneFormat() {
            var phoneField = document.getElementById('billing_phone');
            if (!phoneField) return;

            // 기존 값 포맷
            if (phoneField.value) {
                phoneField.value = formatKRPhone(phoneField.value);
            }

            // 입력 시 자동 포맷
            phoneField.addEventListener('input', function() {
                var digits = this.value.replace(/[^0-9]/g, '');
                if (digits.length <= 3) {
                    this.value = digits;
                } else if (digits.length <= 7) {
                    this.value = digits.substr(0, 3) + '-' + digits.substr(3);
                } else if (digits.length <= 11) {
                    this.value = digits.substr(0, 3) + '-' + digits.substr(3, 4) + '-' + digits.substr(7);
                } else {
                    this.value = digits.substr(0, 3) + '-' + digits.substr(3, 4) + '-' + digits.substr(7, 4);
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPhoneFormat);
        } else {
            initPhoneFormat();
        }

        // 주소 블록에서 중복 전화번호 제거 (PHP 필터 fallback)
        function removeDuplicatePhones() {
            var addresses = document.querySelectorAll('.woocommerce-column--shipping-address address, .woocommerce-column--billing-address address');
            addresses.forEach(function(addr) {
                var html = addr.innerHTML;
                // 전화번호 패턴 (010-XXXX-XXXX 등)
                var phoneRegex = /(<br\s*\/?>)\s*0\d{1,2}[-.\s]?\d{3,4}[-.\s]?\d{4}/g;
                var matches = html.match(phoneRegex);
                if (matches && matches.length > 0) {
                    // 주소 안의 모든 전화번호 라인 제거
                    matches.forEach(function(m) {
                        html = html.replace(m, '');
                    });
                    addr.innerHTML = html;
                }
            });

            // woocommerce-customer-details--phone 중복 제거 (하나만 남기기)
            var phonePs = document.querySelectorAll('.woocommerce-customer-details--phone');
            for (var i = 1; i < phonePs.length; i++) {
                phonePs[i].remove();
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', removeDuplicatePhones);
        } else {
            removeDuplicatePhones();
        }

        // WooCommerce AJAX 업데이트 후에도 재적용
        if (typeof jQuery !== 'undefined') {
            jQuery(document.body).on('updated_checkout', initPhoneFormat);
            jQuery(document.body).on('updated_checkout', removeDuplicatePhones);
        }
    })();
    </script>
    <?php
});
