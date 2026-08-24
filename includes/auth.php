<?php
require_once __DIR__ . '/functions.php';

function current_user(): ?array {
    start_session();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function is_admin(): bool {
    return (current_user()['role'] ?? '') === 'admin';
}

// Call at the top of every protected page.
function require_login(): void {
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

// Call at the top of admin-only pages (settings, lookups, user management).
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        flash('error', 'You do not have permission to access that page.');
        redirect('index.php');
    }
}

function logout(): void {
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
