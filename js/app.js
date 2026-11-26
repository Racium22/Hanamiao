/* ==========================================================================
 * FILE: js/app.js
 * Tương tác nhẹ phía Storefront: nút tăng/giảm số lượng, sao chép người nhận, xác nhận xóa.
 * ========================================================================== */

(function () {
  // 1. Nút +/- số lượng trên trang chi tiết sản phẩm
  document.querySelectorAll('.qty-stepper').forEach(function (stepper) {
    var input = stepper.querySelector('input[type="number"]');
    if (!input) return;

    stepper.querySelectorAll('[data-qty-step]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var step = parseInt(btn.getAttribute('data-qty-step') || '0', 10);
        var min = parseInt(input.getAttribute('min') || '1', 10);
        var max = parseInt(input.getAttribute('max') || '999', 10);
        var current = parseInt(input.value || String(min), 10);
        if (Number.isNaN(current)) current = min;
        var next = Math.min(max, Math.max(min, current + step));
        input.value = String(next);
      });
    });
  });

  // 2. Checkbox "Người nhận giống người đặt" trên trang đặt hàng
  var sameCheckbox = document.getElementById('sameAsBuyer');
  if (sameCheckbox) {
    var buyerName = document.getElementById('ten_khach');
    var buyerPhone = document.getElementById('sdt_khach');
    var recvName = document.getElementById('ten_nguoi_nhan');
    var recvPhone = document.getElementById('sdt_nguoi_nhan');

    function syncRecipient() {
      if (!sameCheckbox.checked) return;
      if (buyerName && recvName) recvName.value = buyerName.value;
      if (buyerPhone && recvPhone) recvPhone.value = buyerPhone.value;
    }

    sameCheckbox.addEventListener('change', syncRecipient);
    if (buyerName) buyerName.addEventListener('input', syncRecipient);
    if (buyerPhone) buyerPhone.addEventListener('input', syncRecipient);
  }

  // 3. Xác nhận trước các hành động nguy hiểm
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var msg = form.getAttribute('data-confirm');
      if (msg && !window.confirm(msg)) {
        event.preventDefault();
      }
    });
  });
})();

