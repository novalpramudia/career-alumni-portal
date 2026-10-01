<?php
require_once __DIR__ . '/../config/auth.php';

// Logout hanya lewat POST + CSRF agar tidak bisa dipicu dari link/gambar pihak lain.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    logout_user();
    session_start();
    flash('success', 'Anda telah keluar.');
    redirect('auth/login.php');
}

redirect(is_logged_in() ? dashboard_path($_SESSION['user']['role']) : 'auth/login.php');
