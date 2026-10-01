<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach ($extraScripts ?? [] as $src): ?>
<script src="<?= e(preg_match('#^https?://#', $src) ? $src : url($src)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
