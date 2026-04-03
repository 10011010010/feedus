/**
 * FEEDUS - 직배송 스케줄 테이블 전치 (행/열 바꿈)
 * 원본: WPCode snippet #333
 *
 * Ninja Tables #329 테이블을 전치합니다.
 * 변경 전: 월/수/금이 컬럼(가로), 지역이 행(세로)
 * 변경 후: 월/수/금이 행(세로), 지역이 컬럼(가로)
 */
(function () {
  'use strict';

  var transposed = false;

  function transposeTable() {
    if (transposed) return;

    var table = document.getElementById('footable_329');
    if (!table) return;

    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    var bodyRows = tbody.querySelectorAll('tr');
    if (!bodyRows.length) return;

    // 원본 데이터 수집 (thead + tbody)
    var headers = [];
    var headerCells = table.querySelectorAll('thead th');
    headerCells.forEach(function (th) {
      headers.push(th.textContent.trim());
    });

    var rows = [];
    bodyRows.forEach(function (tr) {
      var row = [];
      tr.querySelectorAll('td').forEach(function (td) {
        row.push(td.textContent.trim());
      });
      if (row.length) rows.push(row);
    });

    if (!headers.length || !rows.length) return;

    transposed = true;

    var colCount = headers.length;
    var rowCount = rows.length;

    // colgroup 제거
    var colgroup = table.querySelector('colgroup');
    if (colgroup) colgroup.remove();

    // thead 숨김
    var thead = table.querySelector('thead');
    if (thead) thead.style.display = 'none';

    // 새 tbody 생성
    var newTbody = document.createElement('tbody');

    for (var i = 0; i < colCount; i++) {
      var tr = document.createElement('tr');

      // 첫 번째 셀: 요일
      var th = document.createElement('th');
      th.textContent = headers[i];
      th.className = 'feedus-schedule-day';
      th.setAttribute('scope', 'row');
      tr.appendChild(th);

      // 나머지 셀: 지역
      for (var j = 0; j < rowCount; j++) {
        var td = document.createElement('td');
        td.textContent = rows[j][i] || '';
        td.className = 'feedus-schedule-region';
        tr.appendChild(td);
      }

      newTbody.appendChild(tr);
    }

    // tbody 교체
    tbody.parentNode.replaceChild(newTbody, tbody);

    // 테이블에 전치 완료 표시
    table.classList.add('feedus-transposed');
  }

  // 1차: DOM 준비 후 시도
  function init() {
    transposeTable();

    // 2차: Ninja Tables AJAX 로드 대비 - MutationObserver
    if (!transposed) {
      var target = document.getElementById('footable_parent_329');
      if (!target) target = document.body;

      var observer = new MutationObserver(function () {
        transposeTable();
        if (transposed) {
          observer.disconnect();
        }
      });

      observer.observe(target, { childList: true, subtree: true });

      // 안전장치: 10초 후 observer 해제
      setTimeout(function () {
        observer.disconnect();
      }, 10000);
    }

    // 3차: 폴백 - 짧은 딜레이 후 재시도
    if (!transposed) {
      setTimeout(transposeTable, 500);
      setTimeout(transposeTable, 1000);
      setTimeout(transposeTable, 2000);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
