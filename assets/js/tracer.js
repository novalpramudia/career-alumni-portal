// Form tracer study: bagian "Pekerjaan" hanya tampil untuk status bekerja / wirausaha.
document.addEventListener('DOMContentLoaded', function () {
  var status = document.getElementById('employment_status');
  var section = document.getElementById('workSection');
  if (!status || !section) { return; }

  function update() {
    var working = (status.value === 'bekerja' || status.value === 'wirausaha');
    section.hidden = !working;
    section.querySelectorAll('input, select').forEach(function (el) {
      el.disabled = !working; // field tersembunyi tidak ikut terkirim
      if (el.tagName === 'SELECT' || el.type === 'text') { el.required = working; }
    });
  }
  status.addEventListener('change', update);
  update();
});
