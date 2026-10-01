// Live search lowongan: ambil hasil dari api/jobs.php tanpa reload halaman.
// Jika JavaScript mati atau API gagal, form tetap bekerja lewat GET biasa.
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('jobFilter');
  if (!form) { return; }
  var results = document.getElementById('jobResults');
  var pager = document.getElementById('jobPagination');
  var count = document.getElementById('jobCount');
  var timer = null, controller = null;

  function currentParams() {
    var p = new URLSearchParams(new FormData(form));
    Array.from(p.keys()).forEach(function (k) { if (!p.get(k)) { p.delete(k); } });
    return p;
  }

  function load() {
    var p = currentParams();
    if (controller) { controller.abort(); }
    controller = new AbortController();
    results.setAttribute('aria-busy', 'true');

    fetch(form.dataset.api + '?' + p.toString(), { credentials: 'same-origin', signal: controller.signal })
      .then(function (res) { if (!res.ok) { throw new Error('HTTP ' + res.status); } return res.json(); })
      .then(function (data) {
        results.innerHTML = data.html;          // HTML dibuat server dan sudah di-escape
        pager.innerHTML = data.pagination;
        count.textContent = data.total + ' lowongan ditemukan';
        history.replaceState(null, '', p.toString() ? '?' + p.toString() : location.pathname);
        results.removeAttribute('aria-busy');
      })
      .catch(function (err) {
        if (err.name === 'AbortError') { return; }
        form.submit(); // fallback ke pencarian biasa
      });
  }

  form.addEventListener('submit', function (e) { e.preventDefault(); load(); });
  form.querySelectorAll('select').forEach(function (el) { el.addEventListener('change', load); });
  form.querySelector('input[name="q"]').addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(load, 350);
  });
});
