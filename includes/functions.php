<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ext_auth.php';         // ← LMS + local attempt_login()

// ---------- Session ----------
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            // 'secure' => true,  // enable when serving over HTTPS
        ]);
        session_start();
    }
}
// ---------- Escaping ----------
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
// ---------- CSRF ----------
function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function verify_csrf(): void {
    start_session();
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid security token. Please go back and try again.');
    }
}
// ---------- Flash messages ----------
function flash(string $type, string $message): void {
    start_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}
function show_flashes(): void {
    start_session();
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $cls = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'][$f['type']] ?? 'info';
        echo '<div class="alert alert-' . $cls . ' alert-dismissible fade show" role="alert">'
           . e($f['message'])
           . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    unset($_SESSION['flash']);
}
// ---------- Redirect ----------
function redirect(string $path): void {
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}
// ---------- Mail (password reset) ----------
function send_reset_email(string $to, string $name, string $link): bool {
    $subject = APP_NAME . ' — Password reset';
    $body = "Hello $name,\n\nA password reset was requested for your account.\n"
          . "Use the link below within " . RESET_TOKEN_MINUTES . " minutes:\n\n$link\n\n"
          . "If you did not request this, you can ignore this email.\n";
    $headers = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    return mail($to, $subject, $body, $headers);
}
// ---------- Lookup helper: fetch active options for dropdowns ----------
function lookup_options(string $table, string $orderBy = 'name'): array {
    $allowed = ['provinces','districts','disability_types','employment_categories',
                'organisations','institutions','study_levels','fields_of_study',
                'scholarship_statuses','intake_years'];
    if (!in_array($table, $allowed, true)) return [];
    $col = ($table === 'intake_years') ? 'year' : 'name';
    $stmt = db()->query("SELECT * FROM `$table` WHERE is_active = 1 ORDER BY `$col`");
    return $stmt->fetchAll();
}