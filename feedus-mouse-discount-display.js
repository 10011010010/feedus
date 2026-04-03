/**
 * FEEDUS - 마우스 대량 할인 표시
 * 1) 장바구니: 소계와 총계 사이에 할인 행 삽입
 * 2) 상품 페이지: 총 금액 아래에 할인 정보 표시
 *
 * WPCode > JavaScript Snippet > Site Wide Footer
 */
jQuery(function ($) {

    // === 마우스 상품 ID ===
    var MOUSE_IDS = [93, 70];
    var DISCOUNT_PER = 300;
    var MIN_QTY = 50;

    // =============================================
    // 1) 장바구니 페이지 - 할인 행 삽입
    // =============================================
    function updateCartDiscount() {
        var $table = $(".cart_totals .shop_table");
        if (!$table.length) return;

        // 기존 삽입된 행 제거
        $table.find(".feedus-discount-row").remove();

        // 마우스 수량 계산
        var totalMice = 0;
        $(".woocommerce-cart-form .cart_item, .cart-item-row, table.shop_table.cart tbody tr").each(function () {
            var $row = $(this);
            var isMouseRow = false;

            // data-product_id 또는 hidden input에서 product ID 확인
            var productId = $row.find("input.product-id, [name*='product_id']").val();
            if (!productId) {
                // 링크에서 product ID 추출
                var href = $row.find("a[href*='/product/']").attr("href") || "";
                if (href.indexOf("mouse") > -1 || href.indexOf("마우스") > -1) {
                    isMouseRow = true;
                }
                // remove 링크에서 product_id 파라미터 확인
                var removeHref = $row.find("a.remove, .product-remove a").attr("href") || "";
                var match = removeHref.match(/cart_item=[^&]*/);
            }

            // 상품명으로 확인
            var name = $row.find(".product-name, td.product-name").text() || "";
            if (name.indexOf("마우스") > -1) {
                isMouseRow = true;
            }

            if (isMouseRow) {
                var qty = parseInt($row.find("input.qty").val(), 10) || 0;
                totalMice += qty;
            }
        });

        // 소계/총계에서 차이 계산 (백업)
        var subtotalText = $table.find(".cart-subtotal td").text().replace(/[^\d]/g, "");
        var totalText = $table.find(".order-total td").text().replace(/[^\d]/g, "");
        var subtotal = parseInt(subtotalText, 10) || 0;
        var total = parseInt(totalText, 10) || 0;
        var diff = subtotal - total;

        // 할인이 있으면 표시
        if (diff > 0) {
            var discountLabel = "마우스 대량 할인";
            if (totalMice >= MIN_QTY) {
                discountLabel = "마우스 대량 할인 (" + totalMice + "마리)";
            }
            var discountHtml =
                '<tr class="fee feedus-discount-row">' +
                "<th>" + discountLabel + "</th>" +
                '<td data-title="' + discountLabel + '">' +
                '<span class="woocommerce-Price-amount amount"><bdi>' +
                '<span class="woocommerce-Price-currencySymbol">₩</span>' +
                "-" + diff.toLocaleString("ko-KR") +
                "</bdi></span></td></tr>";

            $table.find(".cart-subtotal").after(discountHtml);
        }
    }

    // 장바구니 페이지에서 실행
    if ($("body").hasClass("woocommerce-cart")) {
        updateCartDiscount();
        // AJAX 업데이트 후에도 다시 실행
        $(document.body).on("updated_cart_totals", updateCartDiscount);
    }

    // =============================================
    // 2) 상품 페이지 - 할인 정보 표시
    // =============================================
    function updateProductDiscount() {
        var $summary = $(".wc-summary-table, .wc-locked-variation-summary");
        if (!$summary.length) return;

        // 기존 할인 표시 제거
        $(".feedus-product-discount").remove();

        // 현재 상품이 마우스인지 확인
        var isMouseProduct = false;
        var bodyClass = $("body").attr("class") || "";
        for (var i = 0; i < MOUSE_IDS.length; i++) {
            if (bodyClass.indexOf("postid-" + MOUSE_IDS[i]) > -1) {
                isMouseProduct = true;
                break;
            }
        }
        if (!isMouseProduct) return;

        // 총 수량 계산
        var totalQty = 0;
        $summary.find("input[type='number'], .wc-locked-qty input").each(function () {
            totalQty += parseInt($(this).val(), 10) || 0;
        });

        // 총 금액 행 찾기
        var $totalRow = $summary.find("tr:last, .wc-summary-total");
        var $totalCell = $summary.find("td:contains('₩')").last();

        if (totalQty >= MIN_QTY) {
            var totalDiscount = totalQty * DISCOUNT_PER;

            // 원래 금액 파싱
            var totalText = $totalCell.text().replace(/[^\d]/g, "");
            var originalTotal = parseInt(totalText, 10) || 0;
            var discountedTotal = originalTotal - totalDiscount;

            var html =
                '<div class="feedus-product-discount" style="' +
                "margin-top:12px;padding:12px 16px;border-radius:8px;" +
                "background:#f0fdf4;border:1px solid #86efac;" +
                "font-size:14px;line-height:1.6;" +
                '">' +
                '<div style="color:#16a34a;font-weight:700;margin-bottom:4px;">' +
                "🎉 대량 할인 적용!" +
                "</div>" +
                "<div>총 수량: " + totalQty + "마리 (50마리 이상)</div>" +
                "<div>마리당 할인: -₩" + DISCOUNT_PER.toLocaleString("ko-KR") + "</div>" +
                '<div style="font-weight:700;color:#dc2626;font-size:16px;margin-top:4px;">' +
                "할인 금액: -₩" + totalDiscount.toLocaleString("ko-KR") +
                "</div>" +
                '<div style="font-weight:700;font-size:18px;margin-top:4px;">' +
                "할인 적용가: ₩" + discountedTotal.toLocaleString("ko-KR") +
                "</div>" +
                "</div>";

            // 총 금액 아래 또는 요약 테이블 아래에 삽입
            $summary.after(html);
        } else if (totalQty > 0) {
            var remaining = MIN_QTY - totalQty;
            var html =
                '<div class="feedus-product-discount" style="' +
                "margin-top:12px;padding:12px 16px;border-radius:8px;" +
                "background:#fefce8;border:1px solid #fde047;" +
                "font-size:14px;line-height:1.6;" +
                '">' +
                '<div style="color:#ca8a04;font-weight:600;">' +
                "💡 " + remaining + "마리 더 담으면 마리당 ₩" + DISCOUNT_PER.toLocaleString("ko-KR") + " 할인!" +
                "</div>" +
                "</div>";
            $summary.after(html);
        }
    }

    // 상품 페이지에서 실행
    if ($("body").hasClass("single-product")) {
        // 초기 실행 + 수량 변경 시 + MutationObserver
        var runProductDiscount = function () {
            setTimeout(updateProductDiscount, 300);
        };

        runProductDiscount();
        $(document).on("change", "input[type='number']", runProductDiscount);
        $(document).on("click", ".wc-locked-qty button, .quantity button, .plus, .minus", runProductDiscount);

        // DOM 변경 감지
        var observer = new MutationObserver(function () {
            runProductDiscount();
        });
        var target = document.querySelector(".wc-summary-table, .wc-locked-variation-summary, .summary");
        if (target) {
            observer.observe(target, { childList: true, subtree: true, characterData: true });
        }
    }
});
