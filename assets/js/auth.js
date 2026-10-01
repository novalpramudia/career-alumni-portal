// Halaman register: tampilkan field sesuai role yang dipilih.
document.addEventListener('DOMContentLoaded', function () {
  var radios = document.querySelectorAll('input[name="role"]');
  var alumniOnly = document.querySelectorAll('[data-role-only="alumni"]');
  var nameLabel = document.getElementById('nameLabel');
  var nim = document.getElementById('nim');

  function update() {
    var checked = document.querySelector('input[name="role"]:checked');
    var role = checked ? checked.value : 'alumni';
    alumniOnly.forEach(function (el) { el.hidden = (role !== 'alumni'); });
    if (nim) { nim.required = (role === 'alumni'); }
    if (nameLabel) { nameLabel.textContent = role === 'alumni' ? 'Nama lengkap' : 'Nama perusahaan'; }
  }

  radios.forEach(function (r) { r.addEventListener('change', update); });
  update();
});
