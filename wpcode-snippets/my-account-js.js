/* ==========================================================================
   FEEDUS - My Account 주소 관리 (카카오 우편번호 API)
   WPCode snippet #367 (JS 스니펫 - 사이트 전체 헤더)

   [중요] 이 파일은 WPCode에서 "JavaScript" 타입 스니펫으로 등록합니다.
   WPCode가 자동으로 <script> 태그를 감싸므로, 이 파일 안에
   <script> 또는 </scrip t> 태그를 절대 넣지 마세요.

   카카오 우편번호 API는 WordPress 글로벌 헤더에서 별도로 로드:
   (WPCode가 아닌 워드프레스 설정 > 헤더 스크립트에서 로드)
   ========================================================================== */

(function () {
  'use strict';

  // WooCommerce 주소 편집 폼 페이지 (/edit-address/shipping 등)
  var addressForm = document.querySelector('.woocommerce-address-fields');
  if (addressForm) {
    initAddressEdit();
    return;
  }

  // WooCommerce 주소 표시 페이지 (/edit-address/)
  var addressDisplay = document.querySelector('.woocommerce-Addresses');
  if (addressDisplay) {
    initInlineAddressForm();
  }

  /* --------------------------------------------------------------------------
     주소 표시 페이지 -> 인라인 편집 폼으로 교체
     -------------------------------------------------------------------------- */
  function initInlineAddressForm() {
    var addressColumns = addressDisplay.querySelectorAll('.woocommerce-Address');

    addressColumns.forEach(function (col) {
      var header = col.querySelector('.woocommerce-Address-title');
      var addressEl = col.querySelector('address');
      var editLink = col.querySelector('a.edit');
      if (!addressEl) return;

      // shipping / billing 구분
      var type = 'shipping';
      if (col.classList.contains('u-column2') || (editLink && editLink.href.indexOf('billing') > -1)) {
        type = 'billing';
      }

      // 기존 주소 파싱
      var parsed = parseAddress(addressEl.innerHTML);

      // 기존 주소 표시 숨기기
      addressEl.style.display = 'none';
      if (editLink) editLink.style.display = 'none';

      // 인라인 편집 폼 생성
      var form = createAddressForm(type, parsed);
      col.appendChild(form);
    });
  }

  /* --------------------------------------------------------------------------
     기존 주소 HTML 파싱
     -------------------------------------------------------------------------- */
  function parseAddress(html) {
    var parts = html.split('<br>').map(function (s) {
      return s.replace(/<[^>]*>/g, '').trim();
    }).filter(Boolean);

    // WooCommerce KR 주소 형식: 이름 / 주소1 / 주소2 / 시도 / 우편번호
    return {
      name: parts[0] || '',
      address1: parts[1] || '',
      address2: parts[2] || '',
      state: parts[3] || '',
      postcode: parts[4] || '',
      phone: parts[5] || ''
    };
  }

  /* --------------------------------------------------------------------------
     인라인 주소 편집 폼 생성
     -------------------------------------------------------------------------- */
  function createAddressForm(type, parsed) {
    var wrapper = document.createElement('div');
    wrapper.className = 'feedus-address-inline-edit';

    wrapper.innerHTML =
      '<div class="form-row">' +
        '<label for="feedus_' + type + '_phone">전화번호</label>' +
        '<input type="tel" id="feedus_' + type + '_phone" value="' + escAttr(parsed.phone) + '" placeholder="010-0000-0000" pattern="[0-9]{2,3}-[0-9]{3,4}-[0-9]{4}">' +
      '</div>' +
      '<div class="form-row" style="display:flex;gap:8px;align-items:flex-end;">' +
        '<div style="flex:1;">' +
          '<label for="feedus_' + type + '_postcode">우편번호</label>' +
          '<input type="text" id="feedus_' + type + '_postcode" value="' + escAttr(parsed.postcode) + '" readonly style="background:#f5f5f5;cursor:pointer;">' +
        '</div>' +
        '<button type="button" class="button feedus-postcode-search" id="feedus_' + type + '_search">우편번호 검색</button>' +
      '</div>' +
      '<div class="form-row">' +
        '<label for="feedus_' + type + '_address1">주소</label>' +
        '<input type="text" id="feedus_' + type + '_address1" value="' + escAttr(parsed.address1) + '" readonly style="background:#f5f5f5;">' +
      '</div>' +
      '<div class="form-row">' +
        '<label for="feedus_' + type + '_address2">상세주소</label>' +
        '<input type="text" id="feedus_' + type + '_address2" value="' + escAttr(parsed.address2) + '" placeholder="상세주소를 입력하세요">' +
      '</div>' +
      '<div class="feedus-address-actions">' +
        '<button type="button" class="button feedus-address-save" id="feedus_' + type + '_save">저장</button>' +
      '</div>';

    // 우편번호 검색 버튼 이벤트
    setTimeout(function () {
      var searchBtn = document.getElementById('feedus_' + type + '_search');
      var postcodeInput = document.getElementById('feedus_' + type + '_postcode');
      var address1Input = document.getElementById('feedus_' + type + '_address1');
      var address2Input = document.getElementById('feedus_' + type + '_address2');

      if (searchBtn) {
        searchBtn.addEventListener('click', function () {
          openDaumPostcode(type);
        });
      }
      if (postcodeInput) {
        postcodeInput.addEventListener('click', function () {
          openDaumPostcode(type);
        });
      }

      // 저장 버튼
      var saveBtn = document.getElementById('feedus_' + type + '_save');
      if (saveBtn) {
        saveBtn.addEventListener('click', function () {
          saveAddress(type);
        });
      }
    }, 0);

    return wrapper;
  }

  /* --------------------------------------------------------------------------
     카카오 우편번호 검색
     -------------------------------------------------------------------------- */
  function openDaumPostcode(type) {
    if (typeof daum === 'undefined' || !daum.Postcode) {
      alert('카카오 우편번호 API가 로드되지 않았습니다.');
      return;
    }

    new daum.Postcode({
      oncomplete: function (data) {
        var postcodeInput = document.getElementById('feedus_' + type + '_postcode');
        var address1Input = document.getElementById('feedus_' + type + '_address1');
        var address2Input = document.getElementById('feedus_' + type + '_address2');

        postcodeInput.value = data.zonecode;

        var fullAddress = data.roadAddress || data.jibunAddress;
        if (data.buildingName) {
          fullAddress += ' (' + data.buildingName + ')';
        }
        address1Input.value = fullAddress;

        if (address2Input) {
          address2Input.value = '';
          address2Input.focus();
        }
      }
    }).open();
  }

  /* --------------------------------------------------------------------------
     주소 저장 (WooCommerce AJAX)
     -------------------------------------------------------------------------- */
  function saveAddress(type) {
    var phone = document.getElementById('feedus_' + type + '_phone').value;
    var postcode = document.getElementById('feedus_' + type + '_postcode').value;
    var address1 = document.getElementById('feedus_' + type + '_address1').value;
    var address2 = document.getElementById('feedus_' + type + '_address2').value;

    if (!postcode || !address1) {
      alert('우편번호 검색으로 주소를 입력해주세요.');
      return;
    }

    // WooCommerce 주소 편집 페이지로 폼 데이터 전송
    var formData = new FormData();
    formData.append(type + '_postcode', postcode);
    formData.append(type + '_address_1', address1);
    formData.append(type + '_address_2', address2);
    formData.append(type + '_phone', phone);
    formData.append(type + '_country', 'KR');

    // 시/도 추출 (주소에서)
    var sido = extractSido(address1);
    if (sido) {
      formData.append(type + '_state', sido);
    }

    // 시/군/구 추출
    var city = extractCity(address1);
    if (city) {
      formData.append(type + '_city', city);
    }

    var saveBtn = document.getElementById('feedus_' + type + '_save');
    saveBtn.textContent = '저장 중...';
    saveBtn.disabled = true;

    // WooCommerce edit-address 페이지에 POST로 전송
    var editUrl = '/my-account/edit-address/' + type + '/';

    // 먼저 편집 페이지를 GET으로 가져와서 nonce를 추출
    fetch(editUrl, { credentials: 'same-origin' })
      .then(function (res) { return res.text(); })
      .then(function (html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var nonceField = doc.querySelector('input[name="woocommerce-edit-address-nonce"]');
        var nonce = nonceField ? nonceField.value : '';

        // 폼의 모든 필드를 가져와서 업데이트
        var originalForm = doc.querySelector('.woocommerce-address-fields form') || doc.querySelector('form.edit-address');
        var submitData = new FormData();

        if (originalForm) {
          // 원본 폼의 모든 input 값을 복사
          var inputs = originalForm.querySelectorAll('input, select, textarea');
          inputs.forEach(function (input) {
            if (input.name) {
              submitData.set(input.name, input.value || '');
            }
          });
        }

        // 우리가 수정한 값으로 덮어쓰기
        submitData.set(type + '_country', 'KR');
        submitData.set(type + '_postcode', postcode);
        submitData.set(type + '_address_1', address1);
        submitData.set(type + '_address_2', address2);
        submitData.set(type + '_phone', phone);
        if (sido) submitData.set(type + '_state', sido);
        if (city) submitData.set(type + '_city', city);
        submitData.set('woocommerce-edit-address-nonce', nonce);
        submitData.set('action', 'edit_address');
        submitData.set('save_address', 'Save address');

        return fetch(editUrl, {
          method: 'POST',
          credentials: 'same-origin',
          body: submitData
        });
      })
      .then(function (res) {
        if (res.ok) {
          saveBtn.textContent = '저장 완료!';
          setTimeout(function () {
            window.location.reload();
          }, 500);
        } else {
          throw new Error('저장 실패');
        }
      })
      .catch(function (err) {
        alert('주소 저장에 실패했습니다. 다시 시도해주세요.');
        saveBtn.textContent = '저장';
        saveBtn.disabled = false;
      });
  }

  /* --------------------------------------------------------------------------
     주소 편집 페이지 - 카카오 우편번호 API 연동 (기존 WooCommerce 폼)
     -------------------------------------------------------------------------- */
  function initAddressEdit() {
    var prefix = getAddressPrefix();
    if (!prefix) return;

    var postcodeField = document.getElementById(prefix + '_postcode');
    var address1Field = document.getElementById(prefix + '_address_1');
    var address2Field = document.getElementById(prefix + '_address_2');
    var stateField = document.getElementById(prefix + '_state');
    var cityField = document.getElementById(prefix + '_city');

    if (!postcodeField || !address1Field) return;

    // 우편번호 검색 버튼 생성
    var searchBtn = document.createElement('button');
    searchBtn.type = 'button';
    searchBtn.className = 'button feedus-postcode-search';
    searchBtn.textContent = '우편번호 검색';

    var postcodeWrapper = postcodeField.closest('.form-row') || postcodeField.parentElement;
    postcodeWrapper.style.display = 'flex';
    postcodeWrapper.style.alignItems = 'center';
    postcodeWrapper.style.flexWrap = 'wrap';
    postcodeField.insertAdjacentElement('afterend', searchBtn);

    postcodeField.readOnly = true;
    address1Field.readOnly = true;
    postcodeField.style.backgroundColor = '#f5f5f5';
    address1Field.style.backgroundColor = '#f5f5f5';

    searchBtn.addEventListener('click', function () {
      openDaumPostcodeForForm(prefix, postcodeField, address1Field, address2Field, stateField, cityField);
    });
    postcodeField.addEventListener('click', function () {
      openDaumPostcodeForForm(prefix, postcodeField, address1Field, address2Field, stateField, cityField);
    });

    if (address2Field) {
      address2Field.placeholder = '상세주소를 입력하세요';
    }
  }

  function openDaumPostcodeForForm(prefix, postcodeField, address1Field, address2Field, stateField, cityField) {
    if (typeof daum === 'undefined' || !daum.Postcode) {
      alert('카카오 우편번호 API가 로드되지 않았습니다.');
      return;
    }

    new daum.Postcode({
      oncomplete: function (data) {
        postcodeField.value = data.zonecode;

        var fullAddress = data.roadAddress || data.jibunAddress;
        if (data.buildingName) {
          fullAddress += ' (' + data.buildingName + ')';
        }
        address1Field.value = fullAddress;

        if (stateField) {
          var sido = convertSido(data.sido);
          if (stateField.tagName === 'SELECT') {
            var option = stateField.querySelector('option[value="' + sido + '"]');
            stateField.value = option ? sido : data.sido;
          } else {
            stateField.value = data.sido;
          }
          stateField.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (cityField) {
          cityField.value = data.sigungu;
        }

        if (address2Field) {
          address2Field.value = '';
          address2Field.focus();
        }

        postcodeField.dispatchEvent(new Event('change', { bubbles: true }));
        address1Field.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }).open();
  }

  /* --------------------------------------------------------------------------
     유틸리티
     -------------------------------------------------------------------------- */
  function getAddressPrefix() {
    if (document.getElementById('shipping_postcode')) return 'shipping';
    if (document.getElementById('billing_postcode')) return 'billing';
    if (window.location.href.indexOf('edit-address/shipping') > -1) return 'shipping';
    if (window.location.href.indexOf('edit-address/billing') > -1) return 'billing';
    return null;
  }

  function escAttr(str) {
    return (str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function extractSido(address) {
    var match = address.match(/^(서울|부산|대구|인천|광주|대전|울산|세종|경기|강원|충북|충남|전북|전남|경북|경남|제주)/);
    if (match) return convertSido(match[1]);
    return '';
  }

  function extractCity(address) {
    var match = address.match(/(?:서울|부산|대구|인천|광주|대전|울산|세종|경기|강원|충북|충남|전북|전남|경북|경남|제주)\s+(\S+[시군구])/);
    return match ? match[1] : '';
  }

  function convertSido(sido) {
    var map = {
      '서울': 'KR-11', '서울특별시': 'KR-11',
      '부산': 'KR-26', '부산광역시': 'KR-26',
      '대구': 'KR-27', '대구광역시': 'KR-27',
      '인천': 'KR-28', '인천광역시': 'KR-28',
      '광주': 'KR-29', '광주광역시': 'KR-29',
      '대전': 'KR-30', '대전광역시': 'KR-30',
      '울산': 'KR-31', '울산광역시': 'KR-31',
      '세종': 'KR-36', '세종특별자치시': 'KR-36',
      '경기': 'KR-41', '경기도': 'KR-41',
      '강원': 'KR-42', '강원도': 'KR-42', '강원특별자치도': 'KR-42',
      '충북': 'KR-43', '충청북도': 'KR-43',
      '충남': 'KR-44', '충청남도': 'KR-44',
      '전북': 'KR-45', '전라북도': 'KR-45', '전북특별자치도': 'KR-45',
      '전남': 'KR-46', '전라남도': 'KR-46',
      '경북': 'KR-47', '경상북도': 'KR-47',
      '경남': 'KR-48', '경상남도': 'KR-48',
      '제주': 'KR-49', '제주특별자치도': 'KR-49'
    };
    return map[sido] || sido;
  }
})();
