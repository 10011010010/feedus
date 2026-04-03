/**
 * FEEDUS - My Account 카카오 로그인 버튼 삽입
 * 원본: WPCode snippet #551
 *
 * WooCommerce 로그인 폼 위에 카카오 로그인 버튼 + 구분선을 삽입합니다.
 */
(function () {
  'use strict';

  // 로그인 폼 페이지가 아니면 종료
  var loginH2 = document.querySelector('.woocommerce-account .u-column1 > h2');
  if (!loginH2) return;

  // 카카오 버튼 + 구분선 HTML
  var kakaoHTML =
    '<div class="feedus-kakao-wrap">' +
      '<a href="javascript:loginWithKakao()" class="feedus-kakao-btn">' +
        '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="#191919" d="M12 3C6.48 3 2 6.36 2 10.44c0 2.62 1.74 4.93 4.38 6.24l-1.12 4.16c-.1.36.3.65.62.45l4.96-3.28c.38.04.76.06 1.16.06 5.52 0 10-3.36 10-7.63C22 6.36 17.52 3 12 3z"/></svg>' +
        '<span>카카오 로그인</span>' +
      '</a>' +
      '<div class="feedus-divider"><span>또는</span></div>' +
    '</div>';

  // h2 바로 뒤에 삽입
  loginH2.insertAdjacentHTML('afterend', kakaoHTML);
})();
