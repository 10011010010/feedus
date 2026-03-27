/**
 * FEEDUS - 직배송 지역 테이블 전치 (행/열 바꿈)
 * WPCode > JS 스니펫 > 사이트 전체 푸터에 추가
 *
 * Ninja Tables #329 테이블을 전치합니다.
 * 변경 전: 월/수/금이 컬럼(가로), 지역이 행(세로)
 * 변경 후: 월/수/금이 행(세로), 지역이 컬럼(가로)
 */
(function () {
  'use strict';

  function transposeTable() {
    var table = document.getElementById('footable_329');
    if (!table) return;

    // 원본 데이터 수집 (thead + tbody)
    var headers = [];
    var headerCells = table.querySelectorAll('thead th');
    headerCells.forEach(function (th) {
      headers.push(th.textContent.trim());
    });

    var rows = [];
    var bodyRows = table.querySelectorAll('tbody tr');
    bodyRows.forEach(function (tr) {
      var row = [];
      tr.querySelectorAll('td').forEach(function (td) {
        row.push(td.textContent.trim());
      });
      rows.push(row);
    });

    if (!headers.length || !rows.length) return;

    // 전치: headers[i]가 첫 번째 셀, rows[j][i]가 나머지 셀
    var colCount = headers.length;
    var rowCount = rows.length;

    // 새 thead 생성 (빈 첫 번째 셀 + 지역1~N)
    var newThead = document.createElement('thead');
    var newHeadRow = document.createElement('tr');
    newHeadRow.className = 'footable-header';

    // 첫 번째 빈 헤더 (요일 컬럼)
    var emptyTh = document.createElement('th');
    emptyTh.textContent = '';
    emptyTh.className = 'footable-first-visible';
    newHeadRow.appendChild(emptyTh);

    // 숨김 처리 (헤더 불필요 시)
    newThead.style.display = 'none';
    newThead.appendChild(newHeadRow);

    // 새 tbody 생성
    var newTbody = document.createElement('tbody');

    for (var i = 0; i < colCount; i++) {
      var tr = document.createElement('tr');
      tr.className = 'ninja_table_row_' + i;

      // 첫 번째 셀: 요일 (th)
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

    // 기존 thead/tbody 교체
    var oldThead = table.querySelector('thead');
    var oldTbody = table.querySelector('tbody');

    if (oldThead) table.replaceChild(newThead, oldThead);
    if (oldTbody) table.replaceChild(newTbody, oldTbody);
  }

  // DOM 준비 후 실행
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', transposeTable);
  } else {
    transposeTable();
  }
})();
