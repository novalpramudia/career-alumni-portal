// Grafik dashboard admin. Data diambil dari api/stats.php (JSON).
document.addEventListener('DOMContentLoaded', function () {
  var holder = document.getElementById('charts');
  if (!holder || typeof Chart === 'undefined') { return; }

  var palette = ['#1f4e9e', '#e8a317', '#1e8e5a', '#c0392b'];

  function showError(id) {
    var box = document.getElementById(id);
    if (box) { box.innerHTML = '<div class="empty-state">Grafik gagal dimuat.</div>'; }
  }

  fetch(holder.dataset.api, { credentials: 'same-origin' })
    .then(function (res) {
      if (!res.ok) { throw new Error('HTTP ' + res.status); }
      return res.json();
    })
    .then(function (data) {
      new Chart(document.getElementById('chartApplications'), {
        type: 'doughnut',
        data: {
          labels: data.applications_by_status.labels,
          datasets: [{ data: data.applications_by_status.values, backgroundColor: palette, borderWidth: 0 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '62%' }
      });

      new Chart(document.getElementById('chartYear'), {
        type: 'bar',
        data: {
          labels: data.alumni_by_year.labels,
          datasets: [{ label: 'Jumlah alumni', data: data.alumni_by_year.values, backgroundColor: '#1f4e9e', borderRadius: 6 }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
      });
    })
    .catch(function () { showError('chartApplicationsBox'); showError('chartYearBox'); });
});
