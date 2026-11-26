/* ==========================================================================
 * FILE: admin/js/admin.js
 * Khởi tạo Lucide Icons local, sidebar mobile, xác nhận xóa/khóa và dòng động phiếu nhập/hao hụt.
 * ========================================================================== */

(function () {
  // 1. Render toàn bộ icon Lucide từ thư viện local js/vendor/lucide.min.js
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons();
  }

  // 2. Bật/tắt sidebar trên màn hình nhỏ (<= 768px)
  var toggle = document.getElementById('adminSidebarToggle');
  var sidebar = document.getElementById('adminSidebar');
  var backdrop = document.getElementById('adminSidebarBackdrop');
  if (toggle && sidebar && backdrop) {
    function closeSidebar() {
      sidebar.classList.remove('is-open');
      backdrop.classList.remove('is-open');
    }
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('is-open');
      backdrop.classList.toggle('is-open');
    });
    backdrop.addEventListener('click', closeSidebar);
  }

  // 3. Xác nhận trước hành động nguy hiểm
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var msg = form.getAttribute('data-confirm');
      if (msg && !window.confirm(msg)) {
        event.preventDefault();
      }
    });
  });

  // 4. Quản lý dòng động cho Phiếu nhập kho & Phiếu hao hụt
  document.querySelectorAll('[data-dynamic-rows]').forEach(function (container) {
    var tbody = container.querySelector('[data-rows-body]');
    var template = container.querySelector('template[data-row-template]');
    var addBtn = container.querySelector('[data-add-row]');
    var totalEl = container.querySelector('[data-grand-total]');

    if (!tbody || !template || !addBtn) return;

    function recalcTotals() {
      if (!totalEl) return;
      var grand = 0;
      tbody.querySelectorAll('tr').forEach(function (row) {
        var qtyInput = row.querySelector('[data-calc-qty]');
        var priceInput = row.querySelector('[data-calc-price]');
        var lineEl = row.querySelector('[data-line-total]');
        var qty = parseFloat(qtyInput ? qtyInput.value : '0') || 0;
        var price = parseFloat(priceInput ? priceInput.value : '0') || 0;
        var line = Math.max(0, qty) * Math.max(0, price);
        grand += line;
        if (lineEl) {
          lineEl.textContent = Math.round(line).toLocaleString('vi-VN') + ' đ';
        }
      });
      totalEl.textContent = Math.round(grand).toLocaleString('vi-VN') + ' đ';
    }

    function bindRowEvents(row) {
      row.querySelectorAll('[data-calc-qty], [data-calc-price]').forEach(function (inp) {
        inp.addEventListener('input', recalcTotals);
      });
      var removeBtn = row.querySelector('[data-remove-row]');
      if (removeBtn) {
        removeBtn.addEventListener('click', function () {
          if (tbody.querySelectorAll('tr').length <= 1) return;
          row.remove();
          recalcTotals();
        });
      }
    }

    tbody.querySelectorAll('tr').forEach(bindRowEvents);
    recalcTotals();

    addBtn.addEventListener('click', function () {
      var fragment = template.content.cloneNode(true);
      var newRow = fragment.querySelector('tr');
      tbody.appendChild(fragment);
      if (newRow) bindRowEvents(newRow);
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
      }
      recalcTotals();
    });
  });
})();

