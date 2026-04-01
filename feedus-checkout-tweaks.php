/**
 * FEEDUS - Checkout 페이지 커스터마이징
 * WPCode snippet (PHP 스니펫 - 어디서나 실행)
 *
 * 1. "청구 상세 내용" → "고객님의 배송지 정보" 제목 변경
 * 2. 전화번호 국제번호(+82) → 한국식(010-) 포맷 변환
 */

// "청구 상세 내용" 제목 변경
add_filter('gettext', function ($translated, $text, $domain) {
    if ($domain === 'woocommerce' && $text === 'Billing details') {
        return '고객님의 배송지 정보';
    }
    return $translated;
}, 10, 3);

// 체크아웃 페이지에서 전화번호 한국식 포맷으로 변환
add_action('wp_footer', function () {
    if (!is_checkout()) return;
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

        // WooCommerce AJAX 업데이트 후에도 재적용
        if (typeof jQuery !== 'undefined') {
            jQuery(document.body).on('updated_checkout', initPhoneFormat);
        }
    })();
    </script>
    <?php
});
