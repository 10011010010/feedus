/**
 * FEEDUS - Kakao (Daum) Postcode Address Search
 * 체크아웃 & 마이어카운트 주소 편집 페이지에서
 * billing / shipping 주소 필드에 주소 검색 버튼 삽입
 */
(function () {
  'use strict';

  // Daum Postcode API 로드
  function loadDaumPostcodeAPI(callback) {
    if (window.daum && window.daum.Postcode) {
      callback();
      return;
    }
    var script = document.createElement('script');
    script.src = '//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js';
    script.onload = callback;
    document.head.appendChild(script);
  }

  // 주소 검색 버튼 HTML 생성
  function createSearchButton() {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'feedus-address-search-btn';
    btn.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
      '<circle cx="11" cy="11" r="8"></circle>' +
      '<path d="m21 21-4.3-4.3"></path>' +
      '</svg>' +
      '<span>주소 검색</span>';
    return btn;
  }

  // 주소 검색 실행
  function openPostcodeSearch(prefix) {
    loadDaumPostcodeAPI(function () {
      new daum.Postcode({
        oncomplete: function (data) {
          var address = data.userSelectedType === 'R' ? data.roadAddress : data.jibunAddress;
          var extraAddr = '';

          if (data.userSelectedType === 'R') {
            if (data.bname && /[동|로|가]$/g.test(data.bname)) {
              extraAddr += data.bname;
            }
            if (data.buildingName && data.apartment === 'Y') {
              extraAddr += (extraAddr ? ', ' + data.buildingName : data.buildingName);
            }
            if (extraAddr) {
              address += ' (' + extraAddr + ')';
            }
          }

          // 시/도 추출
          var city = data.sido || '';

          // 필드 값 채우기
          var addr1 = document.getElementById(prefix + '_address_1');
          var addr2 = document.getElementById(prefix + '_address_2');
          var cityField = document.getElementById(prefix + '_city');
          var postcodeField = document.getElementById(prefix + '_postcode');

          if (addr1) {
            addr1.value = address;
            addr1.setAttribute('readonly', '');
            addr1.dispatchEvent(new Event('change', { bubbles: true }));
          }
          if (addr2) {
            addr2.value = '';
            addr2.focus();
          }
          if (cityField) {
            cityField.value = city;
            cityField.setAttribute('readonly', '');
            cityField.dispatchEvent(new Event('change', { bubbles: true }));
          }
          if (postcodeField) {
            postcodeField.value = data.zonecode;
            postcodeField.setAttribute('readonly', '');
            postcodeField.dispatchEvent(new Event('change', { bubbles: true }));
          }

          // WooCommerce validation 트리거
          if (typeof jQuery !== 'undefined') {
            jQuery('#' + prefix + '_address_1').trigger('change');
            jQuery('#' + prefix + '_city').trigger('change');
            jQuery('#' + prefix + '_postcode').trigger('change');
            jQuery('body').trigger('update_checkout');
          }
        }
      }).open();
    });
  }

  // 특정 prefix(billing/shipping)에 버튼 삽입
  function injectButton(prefix) {
    var fieldWrapper = document.getElementById(prefix + '_address_1_field');
    if (!fieldWrapper) return;

    // 이미 버튼이 있으면 스킵
    if (fieldWrapper.querySelector('.feedus-address-search-btn')) return;

    var btn = createSearchButton();
    btn.addEventListener('click', function () {
      openPostcodeSearch(prefix);
    });

    // label 다음, input wrapper 앞에 삽입
    var label = fieldWrapper.querySelector('label');
    if (label) {
      label.insertAdjacentElement('afterend', btn);
    } else {
      fieldWrapper.prepend(btn);
    }
  }

  // 초기화
  function init() {
    injectButton('billing');
    injectButton('shipping');
  }

  // DOM 준비 후 실행
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // WooCommerce가 동적으로 shipping 필드를 로드하는 경우 대비
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('updated_checkout country_to_state_changed', function () {
      setTimeout(init, 300);
    });
  }

  // MutationObserver로 동적 로드 대응 (마이어카운트 주소 편집 페이지 등)
  var observer = new MutationObserver(function (mutations) {
    for (var i = 0; i < mutations.length; i++) {
      if (mutations[i].addedNodes.length) {
        init();
        break;
      }
    }
  });

  var target = document.getElementById('main') || document.getElementById('content') || document.body;
  observer.observe(target, { childList: true, subtree: true });
})();
