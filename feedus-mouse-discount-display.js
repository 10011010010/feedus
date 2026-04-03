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

        // 플러그인 요약 영역 찾기
        var $container = $(".wc-locked-variations-container");
        var $summaryDiv = $(".wc-locked-variations-summary");
        var $summaryTable = $(".wc-summary-table");
        if (!$summaryTable.length) return;

        // 기존 할인 표시 제거
        $(".feedus-product-discount").remove();

        // 총 수량: wc-locked-variation-qty 인풋들 합산
        var totalQty = 0;
        $container.find("input.wc-locked-variation-qty").each(function () {
            totalQty += parseInt($(this).val(), 10) || 0;
        });

        // 총 금액 파싱 (마지막 행의 td)
        var $totalCell = $summaryTable.find("tr:last td");
        var totalText = $totalCell.text().replace(/[^\d]/g, "");
        var originalTotal = parseInt(totalText, 10) || 0;

        if (totalQty >= MIN_QTY) {
            var totalDiscount = totalQty * DISCOUNT_PER;
            var discountedTotal = originalTotal - totalDiscount;

            // 테이블에 할인 행 추가
            var discountRow =
                '<tr class="feedus-product-discount">' +
                '<th style="text-align:left;padding:6px;border-bottom:1px solid #ddd;color:#dc2626;">할인 금액</th>' +
                '<td style="text-align:right;padding:6px;border-bottom:1px solid #ddd;color:#dc2626;font-weight:bold;">' +
                "-₩" + totalDiscount.toLocaleString("ko-KR") +
                "</td></tr>";
            var finalRow =
                '<tr class="feedus-product-discount">' +
                '<th style="text-align:left;padding:6px;font-weight:bold;color:#16a34a;">할인 적용가</th>' +
                '<td style="text-align:right;padding:6px;font-weight:bold;font-size:1.1em;color:#16a34a;">' +
                "₩" + discountedTotal.toLocaleString("ko-KR") +
                "</td></tr>";

            $summaryTable.find("tr:last").after(discountRow + finalRow);

            // 테이블 아래에 안내 배너
            var banner =
                '<div class="feedus-product-discount" style="' +
                "margin-top:10px;padding:10px 14px;border-radius:6px;" +
                "background:#f0fdf4;border:1px solid #86efac;" +
                "font-size:13px;line-height:1.5;color:#16a34a;" +
                '">' +
                "대량 할인 적용 중! (" + totalQty + "마리 × -₩" + DISCOUNT_PER.toLocaleString("ko-KR") + ")" +
                "</div>";
            $summaryDiv.after(banner);

        } else if (totalQty > 0) {
            var remaining = MIN_QTY - totalQty;
            var hint =
                '<div class="feedus-product-discount" style="' +
                "margin-top:10px;padding:10px 14px;border-radius:6px;" +
                "background:#fefce8;border:1px solid #fde047;" +
                "font-size:13px;line-height:1.5;color:#ca8a04;" +
                '">' +
                remaining + "마리 더 담으면 마리당 ₩" + DISCOUNT_PER.toLocaleString("ko-KR") + " 할인!" +
                "</div>";
            $summaryDiv.after(hint);
        }
    }

    // 상품 페이지에서 실행
    if ($("body").hasClass("single-product")) {
        var _discountTimer;
        var runProductDiscount = function () {
            clearTimeout(_discountTimer);
            _discountTimer = setTimeout(updateProductDiscount, 200);
        };

        // 이벤트 바인딩
        $(document).on("change", "input.wc-locked-variation-qty", runProductDiscount);
        $(document).on("click", ".wc-locked-variation-actions button, .quantity button, .plus, .minus", runProductDiscount);
        $(document).on("input", "input.wc-locked-variation-qty", runProductDiscount);

        // body에 MutationObserver (플러그인이 동적으로 DOM을 생성하므로)
        var observer = new MutationObserver(function (mutations) {
            for (var j = 0; j < mutations.length; j++) {
                var t = mutations[j].target;
                if (t && (
                    (t.className && t.className.toString().indexOf("wc-") > -1) ||
                    (t.nodeName === "INPUT") ||
                    (t.nodeName === "TD") ||
                    (t.nodeName === "TABLE")
                )) {
                    runProductDiscount();
                    return;
                }
            }
            // addedNodes 체크
            for (var k = 0; k < mutations.length; k++) {
                if (mutations[k].addedNodes.length) {
                    runProductDiscount();
                    return;
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true, characterData: true });

        // 초기 실행 (약간 딜레이)
        setTimeout(runProductDiscount, 500);
        setTimeout(runProductDiscount, 1500);
    }
});
