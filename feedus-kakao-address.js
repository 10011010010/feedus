/**
 * FEEDUS - Kakao (Daum) Postcode Address Search
 * WooCommerce 체크아웃 주소 검색 팝업 연동
 *
 * 사용법: WPCode 등에서 사이트 전체 헤더 또는 체크아웃 페이지에 삽입
 * <script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
 * <script src="/wp-content/uploads/feedus-kakao-address.js"></script>
 */
(function ($) {
  'use strict';

  // 다음 우편번호 API 로드 확인
  function isDaumPostcodeReady() {
    return typeof daum !== 'undefined' && typeof daum.Postcode !== 'undefined';
  }

  // 주소 검색 팝업 실행
  function openPostcodePopup(prefix) {
    if (!isDaumPostcodeReady()) {
      alert('주소 검색 서비스를 불러오는 중입니다. 잠시 후 다시 시도해 주세요.');
      return;
    }

    new daum.Postcode({
      oncomplete: function (data) {
        // 도로명 주소 우선, 없으면 지번 주소
        var roadAddr = data.roadAddress;
        var jibunAddr = data.jibunAddress;
        var addr = roadAddr || jibunAddr;

        // 참고 항목 (건물명 등)
        var extraAddr = '';
        if (data.addressType === 'R') {
          if (data.bname && /[동|로|가]$/g.test(data.bname)) {
            extraAddr += data.bname;
          }
          if (data.buildingName) {
            extraAddr += (extraAddr ? ', ' : '') + data.buildingName;
          }
          if (extraAddr) {
            extraAddr = ' (' + extraAddr + ')';
          }
        }

        // 필드에 값 채우기
        var $postcode = $('#' + prefix + '_postcode');
        var $address1 = $('#' + prefix + '_address_1');
        var $address2 = $('#' + prefix + '_address_2');
        var $city = $('#' + prefix + '_city');

        $postcode.val(data.zonecode).trigger('change');
        $address1.val(addr + extraAddr).trigger('change');
        $city.val(data.sido).trigger('change');

        // 상세주소 필드로 포커스 이동
        $address2.val('').focus();

        // WooCommerce 주문 요약 갱신 트리거
        $('body').trigger('update_checkout');
      },
      width: '100%',
      height: '100%',
    }).open();
  }

  // 주소 검색 버튼 생성
  function createSearchButton(prefix) {
    var $addressField = $('#' + prefix + '_address_1_field');
    if (!$addressField.length) return;
    if ($addressField.find('.feedus-address-search-btn').length) return;

    var $btn = $(
      '<button type="button" class="feedus-address-search-btn">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>' +
        '</svg>' +
        '<span>주소 검색</span>' +
        '</button>'
    );

    $btn.on('click', function (e) {
      e.preventDefault();
      openPostcodePopup(prefix);
    });

    // 주소 필드 앞에 버튼 삽입
    $addressField.find('label').after($btn);
  }

  // 주소 필드 readonly 처리 (검색으로만 입력)
  function setFieldsReadonly(prefix) {
    $('#' + prefix + '_postcode').prop('readonly', true);
    $('#' + prefix + '_address_1').prop('readonly', true);
    $('#' + prefix + '_city').prop('readonly', true);
  }

  // 주소 필드 클릭 시에도 팝업 열기
  function bindFieldClick(prefix) {
    $('#' + prefix + '_postcode, #' + prefix + '_address_1, #' + prefix + '_city').on(
      'click focus',
      function (e) {
        // 이미 값이 있으면 무시 (수정하려면 버튼 클릭)
        if (!$(this).val()) {
          e.preventDefault();
          openPostcodePopup(prefix);
        }
      }
    );
  }

  // 초기화
  function init() {
    // 체크아웃 페이지인지 확인
    if (!$('form.woocommerce-checkout').length) return;

    // 청구(billing) 주소 검색 버튼
    createSearchButton('billing');
    setFieldsReadonly('billing');
    bindFieldClick('billing');

    // 배송(shipping) 주소 검색 버튼 - "다른 주소로 배송" 체크 시
    createSearchButton('shipping');
    setFieldsReadonly('shipping');
    bindFieldClick('shipping');

    // WooCommerce가 checkout 폼을 업데이트할 때 다시 바인딩
    $(document.body).on('updated_checkout', function () {
      createSearchButton('billing');
      createSearchButton('shipping');
    });
  }

  // DOM 준비 후 실행
  $(document).ready(init);
})(jQuery);
