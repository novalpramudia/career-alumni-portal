// Modal masukan alumni (textContent = aman dari XSS).
document.addEventListener('DOMContentLoaded', function () {
  var modal = document.getElementById('feedbackModal');
  if (!modal) { return; }
  modal.addEventListener('show.bs.modal', function (event) {
    var btn = event.relatedTarget;
    modal.querySelector('[data-name]').textContent = btn.getAttribute('data-name');
    modal.querySelector('[data-feedback]').textContent = btn.getAttribute('data-feedback');
  });
});
