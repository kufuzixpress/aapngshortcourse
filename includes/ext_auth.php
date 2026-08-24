<?php
// ============================================================
// EXTERNAL AUTHENTICATION — Short Course Awards Database
// Loaded by includes/functions.php — do not require directly.
// ============================================================

function attempt_login(string $username, string $password): array
{
    $pdo = db();

    // ── STEP 1: Look up in local users table ─────────────────
    $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    // ── STEP 2: Not found locally — try LMS ──────────────────
    if (!$user) {
        $ext = getExtDB();

        if ($ext) {
            $roleMap = unserialize(EXT_ROLE_MAP);

            try {
                $stmt2 = $ext->prepare(
                    'SELECT `' . EXT_DB_FIELD_ID       . '` AS ext_id,
                            `' . EXT_DB_FIELD_NAME     . '` AS name,
                            `' . EXT_DB_FIELD_USERNAME . '` AS username,
                            `' . EXT_DB_FIELD_EMAIL    . '` AS email,
                            `' . EXT_DB_FIELD_PASSWORD . '` AS password_hash,
                            `' . EXT_DB_FIELD_STATUS   . '` AS status,
                            `' . EXT_DB_FIELD_ROLE     . '` AS role
                     FROM `' . EXT_DB_TABLE . '`
                     WHERE `' . EXT_DB_FIELD_USERNAME . '` = ?
                        OR `' . EXT_DB_FIELD_EMAIL    . '` = ?
                     LIMIT 1'
                );
                $stmt2->execute([$username, $username]);
                $extUser = $stmt2->fetch();
            } catch (PDOException $e) {
                error_log('LMS auth query failed: ' . $e->getMessage());
                $extUser = null;
            }

            if ($extUser
                && strcasecmp(trim((string)$extUser['status']), 'Active') === 0
                && password_verify($password, $extUser['password_hash'])
            ) {
                $mappedRole = $roleMap[$extUser['role']] ?? 'staff';

                // Check for existing shadow record by ext_user_id, username or email.
                $chk = $pdo->prepare('SELECT * FROM users WHERE ext_user_id = ? OR username = ? OR email = ? LIMIT 1');
                $chk->execute([(int)$extUser['ext_id'], $extUser['username'], $extUser['email']]);
                $shadow = $chk->fetch();

                if (!$shadow) {
                    // First login — create shadow record.
                    // Store the LMS password hash so local fallback works later.
                    $pdo->prepare(
                        'INSERT INTO users
                            (ext_user_id, full_name, username, email, password_hash, role, is_active)
                         VALUES (?, ?, ?, ?, ?, ?, 1)'
                    )->execute([
                        (int)$extUser['ext_id'],
                        $extUser['name'],
                        $extUser['username'],
                        $extUser['email'],
                        $extUser['password_hash'],   // real hash — enables local fallback
                        $mappedRole,
                    ]);
                    $newId = (int)$pdo->lastInsertId();
                    $fetch = $pdo->prepare('SELECT * FROM users WHERE id = ?');
                    $fetch->execute([$newId]);
                    $user = $fetch->fetch();
                } else {
                    // Subsequent login — sync name, email, role, and keep hash current.
                    $pdo->prepare(
                        'UPDATE users
                         SET ext_user_id = ?, full_name = ?, email = ?, password_hash = ?, role = ?
                         WHERE id = ?'
                    )->execute([
                        (int)$extUser['ext_id'],
                        $extUser['name'],
                        $extUser['email'],
                        $extUser['password_hash'],
                        $mappedRole,
                        $shadow['id'],
                    ]);
                    $fetch = $pdo->prepare('SELECT * FROM users WHERE id = ?');
                    $fetch->execute([$shadow['id']]);
                    $user = $fetch->fetch();
                }
            }
        }
    }

    // ── STEP 3: Still no user — give up ──────────────────────
    if (!$user) {
        return [false, 'Invalid username or password.'];
    }

    // ── STEP 4: Local controls — active + lockout ─────────────
    if (!$user['is_active']) {
        return [false, 'This account has been deactivated. Contact an administrator.'];
    }

    if ($user['locked_until'] !== null) {
        if (strtotime($user['locked_until']) > time()) {
            $mins = max(1, (int)ceil((strtotime($user['locked_until']) - time()) / 60));
            return [false, "Account locked after too many failed attempts. Try again in $mins minute(s) or contact an administrator."];
        }
        // Lock expired — reset.
        $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?')
            ->execute([$user['id']]);
        $user['failed_attempts'] = 0;
    }

    // ── STEP 5: Password verify (local row — works for both local + LMS users) ──
    if (!password_verify($password, $user['password_hash'])) {
        $attempts = $user['failed_attempts'] + 1;
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $pdo->prepare(
                'UPDATE users SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?'
            )->execute([$attempts, LOCKOUT_MINUTES, $user['id']]);
            return [false, 'Account locked after ' . MAX_LOGIN_ATTEMPTS . ' failed attempts. Try again in ' . LOCKOUT_MINUTES . ' minutes or contact an administrator.'];
        }
        $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?')
            ->execute([$attempts, $user['id']]);
        $left = MAX_LOGIN_ATTEMPTS - $attempts;
        return [false, "Invalid username or password. $left attempt(s) remaining before lockout."];
    }

    // ── STEP 6: Success ───────────────────────────────────────
    $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?')
        ->execute([$user['id']]);

    start_session();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'        => (int)$user['id'],
        'username'  => $user['username'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
    ];

    return [true, 'Welcome back, ' . $user['full_name'] . '!'];
}