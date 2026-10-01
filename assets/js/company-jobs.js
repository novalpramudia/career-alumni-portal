// Halaman lowongan perusahaan: isi modal konfirmasi hapus.
document.addEventListener('DOMContentLoaded', function () {
  var modal = document.getElementById('deleteJobModal');
  if (!modal) { return; }
  modal.addEventListener('show.bs.modal', function (event) {
    var btn = event.relatedTarget;
    modal.querySelector('input[name="job_id"]').value = btn.getAttribute('data-job-id');
    modal.querySelector('[data-job-title]').textContent = btn.getAttribute('data-job-title');
  });
});
