<?php
// PHP 7.4+ / PDO MySQL. No frameworks or Composer dependencies.
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
date_default_timezone_set('Asia/Manila');

function reply(int $status, array $value): void {
    http_response_code($status);
    echo json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function query(PDO $db, string $sql, array $args = []): PDOStatement {
    $stmt = $db->prepare($sql);
    $stmt->execute($args);
    return $stmt;
}
function text(array $data, string $key): string {
    if (!isset($data[$key]) || !is_scalar($data[$key])) return '';
    return trim((string)$data[$key]);
}
function positiveId($value): int {
    if (!is_scalar($value) || !preg_match('/^[1-9][0-9]{0,8}$/D', (string)$value)) {
        throw new InvalidArgumentException('A valid record or category ID is required.');
    }
    return (int)$value;
}
function logAction(PDO $db, int $userId, string $action): void {
    query($db, 'INSERT INTO activity (user_id, action) VALUES (?, ?)', [$userId, $action]);
}
function validateRecord(PDO $db, array $data): array {
    $type = text($data, 'type');
    $description = text($data, 'description');
    $day = text($data, 'record_date');
    $amount = text($data, 'amount');
    if (!in_array($type, ['income', 'expense'], true)) throw new InvalidArgumentException('Select income or expense.');
    $length = preg_match_all('/./us', $description);
    if ($length === false || $length < 2 || $length > 160) throw new InvalidArgumentException('Description must contain 2–160 characters.');
    // Parse decimal strings directly to integer cents; do not use floating-point amounts.
    if (!preg_match('/^(0|[1-9][0-9]{0,8})(?:\.([0-9]{1,2}))?$/D', $amount, $matches)) {
        throw new InvalidArgumentException('Enter a positive amount, up to 999,999,999.00, with at most two decimals.');
    }
    $cents = (int)$matches[1] * 100 + (int)str_pad($matches[2] ?? '', 2, '0');
    if ($cents < 1 || $cents > 99999999900) throw new InvalidArgumentException('Enter a positive amount up to 999,999,999.00.');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $day);
    if (!$date || $date->format('Y-m-d') !== $day || $day < '1000-01-01' || $day > '9999-12-31') {
        throw new InvalidArgumentException('Enter a valid date.');
    }
    $category = positiveId($data['category_id'] ?? null);
    $cat = query($db, 'SELECT type FROM categories WHERE id = ?', [$category])->fetch();
    if (!$cat || $cat['type'] !== $type) throw new InvalidArgumentException('Choose a category matching the record type.');
    return [$type, $category, $description, $cents, $day];
}

try {
    $config = require __DIR__ . '/config.php';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '28800');
    session_name('sme_finance_session');
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    session_set_cookie_params([
        'lifetime' => 28800,
        'path' => $basePath === '' ? '/' : $basePath . '/',
        'secure' => (bool)$config['secure_cookie'],
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
    $dsn = 'mysql:host=' . $config['host'] . ';port=' . (int)$config['port'] . ';dbname=' . $config['database'] . ';charset=utf8mb4';
    $db = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]);
    $db->exec("SET time_zone = '+00:00'");
    $method = $_SERVER['REQUEST_METHOD'];
    $route = trim((string)($_GET['route'] ?? ''), '/');
    $data = [];
    if ($method !== 'GET') {
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) throw new InvalidArgumentException('Request too large.');
        $raw = file_get_contents('php://input', false, null, 0, 16385);
        if ($raw === false || strlen($raw) > 16384) throw new InvalidArgumentException('Request too large.');
        $payload = json_decode($raw ?: '{}');
        if (json_last_error() !== JSON_ERROR_NONE || !is_object($payload)) throw new InvalidArgumentException('Invalid JSON request.');
        $data = (array)$payload;
    }
    if ($route === 'login' && $method === 'POST') {
        // A small per-session limit prevents repeated accidental submissions.
        $attempts = array_filter($_SESSION['login_attempts'] ?? [], static function ($t) { return $t > time() - 60; });
        if (count($attempts) >= 10) reply(429, ['error' => 'Too many attempts. Wait one minute and try again.']);
        $user = query($db, 'SELECT * FROM users WHERE email = ? AND active = 1', [strtolower(text($data, 'email'))])->fetch();
        $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $attempts[] = time();
            $_SESSION['login_attempts'] = $attempts;
            reply(401, ['error' => 'Email or password is incorrect.']);
        }
        session_regenerate_id(true);
        $_SESSION = ['user_id' => (int)$user['id'], 'csrf' => bin2hex(random_bytes(32)), 'expires' => time() + 28800];
        reply(200, ['ok' => true]);
    }
    if (!isset($_SESSION['user_id'], $_SESSION['expires']) || $_SESSION['expires'] < time()) {
        $_SESSION = [];
        reply(401, ['error' => 'Please sign in to continue.']);
    }
    $user = query($db, 'SELECT id, name, email, role, active FROM users WHERE id = ? AND active = 1', [$_SESSION['user_id']])->fetch();
    if (!$user) {
        $_SESSION = [];
        reply(401, ['error' => 'Please sign in to continue.']);
    }
    $user['id'] = (int)$user['id'];
    $user['active'] = (int)$user['active'];
    $role = $user['role'];
    if ($method !== 'GET' && !hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        reply(403, ['error' => 'Invalid request token. Refresh and try again.']);
    }
    if ($route === 'logout' && $method === 'POST') {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600, 'path' => $params['path'], 'secure' => $params['secure'],
            'httponly' => true, 'samesite' => 'Strict',
        ]);
        session_destroy();
        reply(200, ['ok' => true]);
    }
    if ($route === 'workspace' && $method === 'GET') {
        // SQL JOIN combines record, category and creator data for the financial table.
        $records = query($db, 'SELECT t.*, c.name AS category, u.name AS creator FROM transactions t JOIN categories c ON c.id = t.category_id JOIN users u ON u.id = t.created_by ORDER BY t.record_date DESC, t.id DESC')->fetchAll();
        foreach ($records as &$record) {
            foreach (['id', 'category_id', 'amount_cents', 'created_by'] as $key) $record[$key] = (int)$record[$key];
            $record['reviewed_by'] = $record['reviewed_by'] === null ? null : (int)$record['reviewed_by'];
        }
        unset($record);
        $categories = query($db, 'SELECT id, name, type FROM categories ORDER BY id')->fetchAll();
        foreach ($categories as &$category) $category['id'] = (int)$category['id'];
        unset($category);
        $activity = query($db, 'SELECT a.*, u.name AS actor FROM activity a JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 30')->fetchAll();
        $users = $role === 'admin' ? query($db, 'SELECT id, name, email, role, active FROM users ORDER BY id')->fetchAll() : [];
        foreach ($users as &$member) { $member['id'] = (int)$member['id']; $member['active'] = (int)$member['active']; }
        unset($member);
        reply(200, ['user' => $user, 'csrf' => $_SESSION['csrf'], 'records' => $records, 'categories' => $categories, 'activity' => $activity, 'users' => $users]);
    }
    if (preg_match('#^records/([1-9][0-9]{0,8})/review$#D', $route, $matches) && $method === 'POST') {
        if (!in_array($role, ['accountant', 'admin'], true)) reply(403, ['error' => 'Only accountants and admins can review records.']);
        $id = (int)$matches[1];
        $db->beginTransaction();
        if (!query($db, 'SELECT id FROM transactions WHERE id = ? FOR UPDATE', [$id])->fetch()) {
            $db->rollBack(); reply(404, ['error' => 'Record not found.']);
        }
        query($db, "UPDATE transactions SET status = 'reviewed', reviewed_by = ? WHERE id = ?", [$user['id'], $id]);
        logAction($db, $user['id'], 'Reviewed record #' . $id);
        $db->commit();
        reply(200, ['ok' => true]);
    }
    if (preg_match('#^records(?:/([1-9][0-9]{0,8}))?$#D', $route, $matches)) {
        if (!in_array($role, ['owner', 'admin'], true)) reply(403, ['error' => 'Your role can review records but cannot change financial entries.']);
        $id = isset($matches[1]) ? (int)$matches[1] : null;
        if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) reply(405, ['error' => 'Method not allowed.']);
        if (($method === 'POST' && $id !== null) || ($method !== 'POST' && $id === null)) throw new InvalidArgumentException('Invalid record operation.');
        $values = $method !== 'DELETE' ? validateRecord($db, $data) : [];
        $db->beginTransaction();
        if ($id !== null && !query($db, 'SELECT id FROM transactions WHERE id = ? FOR UPDATE', [$id])->fetch()) {
            $db->rollBack(); reply(404, ['error' => 'Record not found.']);
        }
        if ($method === 'DELETE') {
            query($db, 'DELETE FROM transactions WHERE id = ?', [$id]);
            $action = 'Deleted';
        } elseif ($method === 'POST') {
            $values[] = $user['id'];
            query($db, 'INSERT INTO transactions (type, category_id, description, amount_cents, record_date, created_by) VALUES (?, ?, ?, ?, ?, ?)', $values);
            $id = (int)$db->lastInsertId();
            $action = 'Created';
        } else {
            $values[] = $id;
            query($db, "UPDATE transactions SET type = ?, category_id = ?, description = ?, amount_cents = ?, record_date = ?, status = 'pending', reviewed_by = NULL WHERE id = ?", $values);
            $action = 'Updated';
        }
        logAction($db, $user['id'], $action . ' record #' . $id);
        $db->commit();
        reply(200, ['ok' => true, 'id' => $id]);
    }
    if ($route === 'users' && $method === 'POST') {
        if ($role !== 'admin') reply(403, ['error' => 'Admin access required.']);
        $name = text($data, 'name'); $email = strtolower(text($data, 'email')); $newRole = text($data, 'role');
        $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';
        $nameLength = preg_match_all('/./us', $name);
        if ($nameLength === false || $nameLength < 2 || $nameLength > 80 || strlen($email) > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10 || strlen($password) > 72 || !in_array($newRole, ['owner', 'accountant', 'admin'], true)) {
            throw new InvalidArgumentException('Enter a name, valid email, role and password of 10–72 characters.');
        }
        $db->beginTransaction();
        query($db, 'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)', [$name, $email, password_hash($password, PASSWORD_BCRYPT), $newRole]);
        logAction($db, $user['id'], 'Created ' . $newRole . ' account');
        $db->commit();
        reply(200, ['ok' => true]);
    }
    reply(404, ['error' => 'Not found.']);
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    reply(400, ['error' => $e->getMessage()]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    if ((string)$e->getCode() === '23000') reply(400, ['error' => 'That email already exists or a related record is invalid.']);
    error_log('SME Financial Tracker database error: ' . $e->getMessage());
    reply(503, ['error' => 'Database unavailable. Start MySQL in XAMPP, import database/sme_financial_tracker.sql in phpMyAdmin, and check config.php.']);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('SME Financial Tracker error: ' . $e->getMessage());
    reply(500, ['error' => 'The request could not be completed. Check the Apache/PHP error log.']);
}
