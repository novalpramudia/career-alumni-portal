// Halaman pelamar: tampilkan cover letter di modal (textContent = aman dari XSS).
document.addEventListener('DOMContentLoaded', function () {
  var modal = document.getElementById('letterModal');
  if (!modal) { return; }
  modal.addEventListener('show.bs.modal', function (event) {
    var btn = event.relatedTarget;
    modal.querySelector('[data-name]').textContent = btn.getAttribute('data-name');
    modal.querySelector('[data-letter]').textContent = btn.getAttribute('data-letter');
  });
});
