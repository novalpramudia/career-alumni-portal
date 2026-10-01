// Halaman profil alumni: preview foto dan cek ukuran sebelum upload.
document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('photo');
  var preview = document.getElementById('photoPreview');
  var msg = document.getElementById('photoError');
  if (!input) { return; }
  var MAX = 2 * 1024 * 1024;

  input.addEventListener('change', function () {
    msg.textContent = '';
    var file = input.files[0];
    if (!file) { return; }
    if (['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) === -1) {
      msg.textContent = 'Gunakan file JPG, PNG, atau WEBP.';
      input.value = '';
      return;
    }
    if (file.size > MAX) {
      msg.textContent = 'Ukuran foto maksimal 2 MB.';
      input.value = '';
      return;
    }
    var url = URL.createObjectURL(file);
    if (preview.tagName === 'IMG') {
      preview.src = url;
    } else {
      var img = document.createElement('img');
      img.id = 'photoPreview';
      img.className = preview.className;
      img.alt = 'Foto profil';
      img.src = url;
      preview.replaceWith(img);
      preview = img;
    }
  });
});
