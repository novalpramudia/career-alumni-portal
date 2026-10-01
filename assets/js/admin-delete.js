// Pola modal konfirmasi hapus generik untuk halaman admin (alumni, perusahaan, lowongan, berita).
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var modal = document.getElementById(btn.getAttribute('data-bs-target').slice(1));
      if (!modal) { return; }
      var field = btn.getAttribute('data-field');
      modal.querySelector('input[name="' + field + '"]').value = btn.getAttribute('data-id');
      var backInput = modal.querySelector('input[name="back"]');
      if (backInput) { backInput.value = btn.getAttribute('data-back') || ''; }
      var nameEl = modal.querySelector('[data-name]');
      if (nameEl) { nameEl.textContent = btn.getAttribute('data-name') || ''; }
    });
  });
});
