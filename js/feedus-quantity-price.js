/**
 * FEEDUS - 수량 변경 시 가격 실시간 업데이트
 *
 * 단일 상품 페이지에서 수량(+/-)을 변경하면
 * 표시 가격이 (단가 × 수량)으로 즉시 갱신됩니다.
 *
 * jQuery 필요
 */
jQuery(function ($) {
    if (!$("body").hasClass("single-product")) return;

    var $priceWrap = $(".brxe-product-price .price");
    if (!$priceWrap.length) return;

    // 단가 파싱 (첫 번째 금액 = 현재 가격)
    var priceText = $priceWrap.find(".woocommerce-Price-amount").first().text();
    var unitPrice = parseInt(priceText.replace(/[^\d]/g, ""), 10);
    if (!unitPrice || isNaN(unitPrice)) return;

    // 원본 HTML 보존 (수량 1로 돌아갈 때 복원용)
    var originalHtml = $priceWrap.html();

    function updatePrice() {
        var qty = parseInt($("form.cart input.qty").val(), 10) || 1;
        if (qty <= 1) {
            $priceWrap.html(originalHtml);
            return;
        }

        var total = unitPrice * qty;
        var formatted = total.toLocaleString("ko-KR");

        $priceWrap.html(
            '<span class="woocommerce-Price-amount amount"><bdi>' +
            formatted +
            '<span class="woocommerce-Price-currencySymbol">원</span>' +
            "</bdi></span>"
        );
    }

    // 이벤트: 수량 input 변경, +/- 버튼 클릭
    $(document).on("change input", "form.cart input.qty", updatePrice);
    $(document).on("click", "form.cart .quantity .action.plus, form.cart .quantity .action.minus", function () {
        // 버튼 클릭 후 값이 반영될 때까지 짧은 지연
        setTimeout(updatePrice, 50);
    });
});
