/**
 * FEEDUS - 가격 소수점 제거 및 천단위 콤마 포맷 수정
 * "Add Multiple Variations to Cart" 플러그인이 WooCommerce 소수점 설정을 무시하고
 * ₩22000.00 형식으로 출력하는 문제를 수정합니다.
 *
 * WPCode > JavaScript 스니펫 > 사이트 전체 푸터에 추가
 */
(function () {
  'use strict';

  var currencySymbol = '₩';

  /**
   * 가격 문자열을 한국 원화 형식으로 변환
   * "₩22000.00" → "₩22,000"
   * "₩12000.00" → "₩12,000"
   */
  function formatKRW(text) {
    return text.replace(
      new RegExp(escapeRegex(currencySymbol) + '([\\d,]+)\\.\\d{1,2}', 'g'),
      function (match, digits) {
        var num = parseInt(digits.replace(/,/g, ''), 10);
        if (isNaN(num)) return match;
        return currencySymbol + num.toLocaleString('ko-KR');
      }
    );
  }

  function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  /**
   * 대상 요소 내부의 가격 텍스트를 수정
   */
  function fixPricesIn(container) {
    if (!container) return;

    // 텍스트 노드를 포함한 모든 요소 순회
    var walker = document.createTreeWalker(
      container,
      NodeFilter.SHOW_TEXT,
      null,
      false
    );

    var node;
    while ((node = walker.nextNode())) {
      if (node.nodeValue && node.nodeValue.indexOf('.00') !== -1 && node.nodeValue.indexOf(currencySymbol) !== -1) {
        var fixed = formatKRW(node.nodeValue);
        if (fixed !== node.nodeValue) {
          node.nodeValue = fixed;
        }
      }
      // .0 패턴도 처리 (예: ₩22000.0)
      if (node.nodeValue && /₩[\d,]+\.\d$/.test(node.nodeValue)) {
        node.nodeValue = node.nodeValue.replace(
          new RegExp(escapeRegex(currencySymbol) + '([\\d,]+)\\.\\d$', 'g'),
          function (match, digits) {
            var num = parseInt(digits.replace(/,/g, ''), 10);
            if (isNaN(num)) return match;
            return currencySymbol + num.toLocaleString('ko-KR');
          }
        );
      }
    }
  }

  function fixAllPrices() {
    // 잠긴 옵션 컨테이너 (선택한 옵션 목록)
    fixPricesIn(document.querySelector('.wc-locked-variations-container'));
    // 요약 테이블
    fixPricesIn(document.querySelector('.wc-summary-table'));
    // 전체 add-to-cart 영역
    fixPricesIn(document.querySelector('.brxe-product-add-to-cart'));
  }

  // MutationObserver: 플러그인이 가격을 DOM에 삽입할 때마다 수정
  var target = document.querySelector('.brxe-product-add-to-cart');
  if (target) {
    var observer = new MutationObserver(function () {
      fixAllPrices();
    });
    observer.observe(target, { childList: true, subtree: true, characterData: true });
  }

  // 초기 실행 + 폴백
  fixAllPrices();
  setTimeout(fixAllPrices, 1000);
  setTimeout(fixAllPrices, 2000);
  setTimeout(fixAllPrices, 4000);
})();
