/**
 * FEEDUS - 상품 페이지 Multiple Variations 번역 + 가격 포맷
 * 원본: WPCode snippet #614 (snippet #162 JS 부분 통합)
 *
 * "Add Multiple Variations to Cart" 플러그인 한글화 및
 * ₩22000.00 → ₩22,000 가격 포맷 수정
 * + "모두 장바구니 담기" 즉시 동작 (목록 미등록 시 자동 등록 후 담기)
 * + 단가 × 수량 실시간 표시
 *
 * jQuery 필요
 */
jQuery(function($) {
    var translations = {
        "Selected Variations": "선택한 옵션",
        "Total Variations": "선택 옵션 수",
        "Total Quantity": "총 수량",
        "Total": "총 금액",
        "Add to List": "목록에 담기",
        "Add All to Cart": "모두 장바구니 담기"
    };

    function translateAll() {
        $(".wc-lock-variation-btn").each(function() {
            if ($(this).text().trim() !== "목록에 담기") $(this).text("목록에 담기");
        });
        $(".wc-add-locked-to-cart").each(function() {
            if ($(this).text().trim() !== "모두 장바구니 담기") $(this).text("모두 장바구니 담기");
        });
        $(".wc-locked-variations-container > b").each(function() {
            if ($(this).text().trim() !== "선택한 옵션") $(this).text("선택한 옵션");
        });
        $(".wc-summary-table th").each(function() {
            var text = $(this).text().trim();
            if (translations[text]) $(this).text(translations[text]);
        });
        $("a.wc-forward").each(function() {
            if ($(this).text().trim() === "View cart") $(this).text("장바구니 보기");
        });

        // 가격 포맷 수정:
        // 1) ₩22000.00 → ₩22,000 (₩ 기호 패턴)
        // 2) 원55000.00 → 55,000원 ("원"이 앞에 오는 플러그인 출력 패턴)
        $(".wc-summary-table td, .wc-locked-variation-price").each(function() {
            var el = $(this);
            var html = el.html();
            if (!html) return;

            // ₩ 기호 패턴
            if (/₩[\d,]+\.\d{1,2}/.test(html)) {
                html = html.replace(/₩([\d,]+)\.\d{1,2}/g, function(m, digits) {
                    var num = parseInt(digits.replace(/,/g, ''), 10);
                    return isNaN(num) ? m : '₩' + num.toLocaleString('ko-KR');
                });
            }

            // "원" 기호가 숫자 앞에 오는 패턴: 원55000.00 → 55,000원
            if (/원\d/.test(html)) {
                html = html.replace(/원([\d,]+)(?:\.\d+)?/g, function(m, digits) {
                    var num = parseInt(digits.replace(/,/g, ''), 10);
                    return isNaN(num) ? m : num.toLocaleString('ko-KR') + '원';
                });
            }

            el.html(html);
        });
    }

    /* =========================================================
       "모두 장바구니 담기" — 목록 비어 있으면 자동 등록 후 담기
       ========================================================= */
    function enableAddToCartButton() {
        var $btn = $(".wc-add-locked-to-cart");
        // 항상 클릭 가능하게 disabled 해제
        $btn.prop("disabled", false).removeAttr("disabled");
    }

    var _addToCartBound = false;
    function bindAddToCart() {
        if (_addToCartBound) return;
        _addToCartBound = true;

        // 캡처링 단계에서 잡아야 플러그인 핸들러보다 먼저 실행됨
        document.addEventListener("click", function(e) {
            var btn = e.target.closest(".wc-add-locked-to-cart");
            if (!btn) return;

            var container = document.querySelector(".wc-locked-variations-container");
            var hasLocked = container &&
                container.querySelectorAll(".wc-locked-variation-row, .wc-locked-variation").length > 0;

            if (!hasLocked) {
                // 옵션이 선택되어 있는지 확인
                var variationId = document.querySelector("input.variation_id");
                if (!variationId || !variationId.value || variationId.value === "0") return;

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                // "목록에 담기" 클릭 → 자동 등록
                var lockBtn = document.querySelector(".wc-lock-variation-btn");
                if (lockBtn) lockBtn.click();

                // 등록 후 "모두 장바구니 담기" 재클릭
                setTimeout(function() {
                    btn.disabled = false;
                    btn.removeAttribute("disabled");
                    btn.click();
                }, 400);
            }
        }, true); // true = 캡처링 단계 (플러그인보다 먼저 실행)
    }

    /* =========================================================
       단가 × 수량 실시간 표시
       ========================================================= */
    var _lastVariationPrice = 0;

    function updatePriceByQty() {
        var $form = $("form.variations_form");
        if (!$form.length) return;

        var qty = parseInt($form.find("input.qty").val(), 10) || 1;

        // 현재 선택된 variation의 단가 구하기
        var variationId = parseInt($form.find("input.variation_id").val(), 10);
        if (!variationId) return;

        var variations = $form.data("product_variations") || [];
        var unitPrice = 0;
        for (var i = 0; i < variations.length; i++) {
            if (variations[i].variation_id === variationId) {
                unitPrice = variations[i].display_price;
                break;
            }
        }
        if (!unitPrice) return;
        _lastVariationPrice = unitPrice;

        var totalPrice = unitPrice * qty;

        // .woocommerce-variation-price 안의 가격 업데이트
        var $priceWrap = $form.find(".woocommerce-variation-price");
        if ($priceWrap.length) {
            var formatted = totalPrice.toLocaleString("ko-KR");
            $priceWrap.find(".woocommerce-Price-amount bdi").html(
                formatted + '<span class="woocommerce-Price-currencySymbol">원</span>'
            );
        }
    }

    // 수량 변경 이벤트
    $(document).on("input change", "form.variations_form input.qty", updatePriceByQty);
    $(document).on("click", "form.variations_form .quantity .action.plus, form.variations_form .quantity .action.minus", function() {
        setTimeout(updatePriceByQty, 50);
    });
    // variation 변경 시 수량 1이면 단가 그대로, 아니면 갱신
    $("form.variations_form").on("found_variation", function(e, variation) {
        _lastVariationPrice = variation.display_price;
        setTimeout(updatePriceByQty, 50);
    });

    /* =========================================================
       init
       ========================================================= */
    function init() {
        var $form = $("form.variations_form");
        var $mode = $("#wc_variation_mode");
        if (!$form.length || !$mode.length) return;

        $form.find(".single_add_to_cart_button").hide();

        if ($mode.val() !== "multiple") {
            $mode.val("multiple").trigger("change");
        }

        var $btns = $(".wc-multiple-variation-buttons");
        if ($btns.length && $btns.is(":hidden")) {
            $form.trigger("check_variations");
            $form.trigger("wc_variation_form");
            setTimeout(function() {
                $("#wc_variation_mode").val("multiple").trigger("change");
            }, 300);
        }

        translateAll();
        enableAddToCartButton();
        bindAddToCart();
        updatePriceByQty();
    }

    // MutationObserver: 플러그인이 DOM을 변경할 때마다 번역 + 가격 수정 + 버튼 활성화
    var _observerTimer;
    var observer = new MutationObserver(function() {
        clearTimeout(_observerTimer);
        _observerTimer = setTimeout(function() {
            translateAll();
            enableAddToCartButton();
        }, 50);
    });
    var container = document.querySelector(".brxe-product-add-to-cart");
    if (container) {
        observer.observe(container, { childList: true, subtree: true, characterData: true });
    }

    setTimeout(init, 1000);
    setTimeout(init, 2000);
    setTimeout(init, 4000);
    setTimeout(init, 6000);

    $(document.body).on("wc_variation_form added_to_cart", init);
});
