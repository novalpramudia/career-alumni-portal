<?php
/** Membuat slug unik dari judul (huruf kecil, tanpa spasi/simbol). */
function make_slug(string $title, int $excludeId = 0): string
{
    $base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-'));
    $base = $base !== '' ? $base : 'berita';
    $slug = $base;
    $i = 2;
    while (db_scalar('SELECT COUNT(*) FROM news WHERE slug = ? AND id <> ?', [$slug, $excludeId]) > 0) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}
