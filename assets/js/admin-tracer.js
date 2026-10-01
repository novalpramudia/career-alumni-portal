// Grafik statistik tracer study (data dari api/tracer-stats.php).
document.addEventListener('DOMContentLoaded', function () {
  var holder = document.getElementById('charts');
  if (!holder || typeof Chart === 'undefined') { return; }

  var palette = ['#1f4e9e', '#e8a317', '#1e8e5a', '#c0392b', '#6b7a99'];

  function bar(id, series) {
    new Chart(document.getElementById(id), {
      type: 'bar',
      data: { labels: series.labels, datasets: [{ data: series.values, backgroundColor: '#1f4e9e', borderRadius: 6 }] },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }
  function doughnut(id, series) {
    new Chart(document.getElementById(id), {
      type: 'doughnut',
      data: { labels: series.labels, datasets: [{ data: series.values, backgroundColor: palette, borderWidth: 0 }] },
      options: { maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom' } } }
    });
  }

  fetch(holder.dataset.api, { credentials: 'same-origin' })
    .then(function (res) { if (!res.ok) { throw new Error('HTTP ' + res.status); } return res.json(); })
    .then(function (d) {
      doughnut('chartStatus', d.status);
      bar('chartWaiting', d.waiting);
      bar('chartSalary', d.salary);
      doughnut('chartRelevance', d.relevance);
    })
    .catch(function () {
      ['boxStatus', 'boxWaiting', 'boxSalary', 'boxRelevance'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.innerHTML = '<div class="empty-state">Grafik gagal dimuat.</div>'; }
      });
    });
});
