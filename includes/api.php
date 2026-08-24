<?php
/**
 * Common bootstrap for all api/ endpoints.
 * Every endpoint returns JSON: { success: bool, message: string, data: mixed }
 */
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

function json_out(bool $success, $data = null, string $message = ''): void {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function api_input(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

/** Mutating requests must carry the CSRF token in the X-CSRF-Token header. */
function api_verify_csrf(): void {
    start_session();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        json_out(false, null, 'Invalid security token. Please refresh the page and try again.');
    }
}

function api_require_admin(): void {
    if (!is_admin()) {
        http_response_code(403);
        json_out(false, null, 'Administrator access required.');
    }
}
