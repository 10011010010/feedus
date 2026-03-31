/**
 * FEEDUS: Kakao (Daum) Postcode Address Search for WooCommerce
 * 체크아웃 + 마이어카운트 주소 편집 페이지 통합
 * WPCode > HTML 스니펫 > 사이트 전체 헤더에 추가
 *
 * <script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
 * <script src="이파일경로/feedus-kakao-address.js"></script>
 */
(function ($) {
  'use strict';

  function openPostcodePopup(prefix) {
    if (typeof daum === 'undefined' || typeof daum.Postcode === 'undefined') {
      alert('주소 검색 서비스를 불러오는 중입니다. 잠시 후 다시 시도해 주세요.');
      return;
    }

    new daum.Postcode({
      oncomplete: function (data) {
        var roadAddr = data.roadAddress;
        var jibunAddr = data.jibunAddress;
        var addr = roadAddr || jibunAddr;

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

        $('#' + prefix + '_postcode').val(data.zonecode).trigger('change');
        $('#' + prefix + '_address_1').val(addr + extraAddr).trigger('change');
        $('#' + prefix + '_city').val(data.sido).trigger('change');
        $('#' + prefix + '_address_2').val('').focus();

        $('body').trigger('update_checkout');
      },
      width: '100%',
      height: '100%'
    }).open();
  }

  function createSearchButton(prefix) {
    var $addressField = $('#' + prefix + '_address_1_field');
    if (!$addressField.length) return;
    if ($addressField.find('.feedus-address-search-btn').length) return;

    var $btn = $(
      '<button type="button" class="feedus-address-search-btn">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        '<circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>' +
        '</svg>' +
        '<span>주소 검색</span>' +
      '</button>'
    );

    $btn.on('click', function (e) {
      e.preventDefault();
      openPostcodePopup(prefix);
    });

    $addressField.find('label').after($btn);
  }

  function setFieldsReadonly(prefix) {
    $('#' + prefix + '_postcode').prop('readonly', true);
    $('#' + prefix + '_address_1').prop('readonly', true);
    $('#' + prefix + '_city').prop('readonly', true);
  }

  function bindFieldClick(prefix) {
    $('#' + prefix + '_postcode, #' + prefix + '_address_1, #' + prefix + '_city').on(
      'click focus',
      function (e) {
        if (!$(this).val()) {
          e.preventDefault();
          openPostcodePopup(prefix);
        }
      }
    );
  }

  function setupPrefix(prefix) {
    if (!$('#' + prefix + '_address_1_field').length) return;
    createSearchButton(prefix);
    setFieldsReadonly(prefix);
    bindFieldClick(prefix);
  }

  function init() {
    // 체크아웃 페이지
    var isCheckout = $('form.woocommerce-checkout').length > 0;
    // 마이어카운트 주소 편집 페이지 (/my-account/edit-address/billing/ 또는 /shipping/)
    var isEditAddress = $('form.woocommerce-edit-address').length > 0
                     || $('.woocommerce-address-fields').length > 0;

    if (!isCheckout && !isEditAddress) return;

    setupPrefix('billing');
    setupPrefix('shipping');

    // 체크아웃 동적 업데이트 대응
    if (isCheckout) {
      $(document.body).on('updated_checkout', function () {
        setupPrefix('billing');
        setupPrefix('shipping');
      });
    }
  }

  $(document).ready(init);
})(jQuery);
