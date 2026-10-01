// Form lamaran: cek file CV di browser dan hitung karakter cover letter.
document.addEventListener('DOMContentLoaded', function () {
  var cv = document.getElementById('cv');
  var err = document.getElementById('cvError');
  var letter = document.getElementById('cover_letter');
  var count = document.getElementById('clCount');
  var MAX = 2 * 1024 * 1024;

  if (cv) {
    cv.addEventListener('change', function () {
      err.textContent = '';
      var f = cv.files[0];
      if (!f) { return; }
      if (f.type !== 'application/pdf') {
        err.textContent = 'File harus berformat PDF.';
        cv.value = '';
      } else if (f.size > MAX) {
        err.textContent = 'Ukuran CV maksimal 2 MB.';
        cv.value = '';
      }
    });
  }
  if (letter && count) {
    letter.addEventListener('input', function () { count.textContent = letter.value.length; });
  }
});
