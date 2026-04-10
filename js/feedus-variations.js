/**
 * FEEDUS - 상품 페이지 Multiple Variations 번역 + 가격 포맷
 * 원본: WPCode snippet #614 (snippet #162 JS 부분 통합)
 *
 * "Add Multiple Variations to Cart" 플러그인 한글화 및
 * 원5000.00 → 5,000원 가격 포맷 수정 (한국식 표기)
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

        // 가격 소수점 제거 + 천단위 콤마 + 한국식 표기 (원5000.00 → 5,000원)
        $(".wc-summary-table td, .wc-locked-variation-price").each(function() {
            var el = $(this);
            var html = el.html();
            if (html && /[₩원][\d,]+(\.\d{1,2})?/.test(html)) {
                el.html(html.replace(/[₩원]([\d,]+)(\.\d{1,2})?/g, function(m, digits) {
                    var num = parseInt(digits.replace(/,/g, ''), 10);
                    return isNaN(num) ? m : num.toLocaleString('ko-KR') + '원';
                }));
            }
        });
    }

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
    }

    // MutationObserver: 플러그인이 DOM을 변경할 때마다 번역 + 가격 수정 실행
    var observer = new MutationObserver(function() {
        translateAll();
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
