// Halaman lamaran alumni: isi modal konfirmasi tarik lamaran.
document.addEventListener('DOMContentLoaded', function () {
  var modal = document.getElementById('withdrawModal');
  if (!modal) { return; }
  modal.addEventListener('show.bs.modal', function (event) {
    var btn = event.relatedTarget;
    modal.querySelector('input[name="application_id"]').value = btn.getAttribute('data-app-id');
    modal.querySelector('[data-app-title]').textContent = btn.getAttribute('data-app-title');
  });
});
