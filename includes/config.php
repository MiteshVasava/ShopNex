<?php
// Automatically detect whether ShopNex is deployed at the domain root
// (InfinityFree: /admin/products.php) or inside a local subfolder
// (XAMPP: /online-shopping-center/admin/products.php).
$shopnexScript = (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php');
$shopnexBase = '';
if (preg_match('~^(.*?)/(?:admin|staff)/[^/]+$~', $shopnexScript, $baseMatch)) {
    $shopnexBase = rtrim($baseMatch[1], '/');
} else {
    $shopnexDir = str_replace('\\', '/', dirname($shopnexScript));
    $shopnexBase = ($shopnexDir === '/' || $shopnexDir === '.') ? '' : rtrim($shopnexDir, '/');
}
if (!defined('BASE_URL')) define('BASE_URL', $shopnexBase);

// Keep customer storefront sessions separate from the private admin/staff workspace.
// This allows the public store and operations panel to stay open in different tabs.
$scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
$scriptBase = basename($scriptName);
$privatePortalScripts = ['nex-owner-console-7f3a9c.php', 'nex-staff-console-2d8b6e.php'];
$isOperationsContext = str_contains($scriptName, '/admin/') ||
    str_contains($scriptName, '/staff/') ||
    in_array($scriptBase, $privatePortalScripts, true) ||
    ($scriptBase === 'logout.php' && (($_GET['workspace'] ?? '') === 'operations'));
define('SHOPNEX_OPERATIONS_CONTEXT', $isOperationsContext);
session_name($isOperationsContext ? 'SHOPNEX_OPS' : 'SHOPNEX_PUBLIC');
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_URL !== '' ? BASE_URL : '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Database connection settings.
// XAMPP defaults are shown below. On InfinityFree, replace these four values
// with the exact MySQL Hostname, Database Name, Username, and Password shown
// in the hosting Control Panel. Do not upload real credentials into a public repo.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'online_shopping_center';
const DB_USER = 'root';
const DB_PASS = '';
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('ShopNex database connection failed: ' . $e->getMessage());
            http_response_code(503);
            exit('ShopNex cannot connect to its database. Check DB_HOST, DB_NAME, DB_USER and DB_PASS in includes/config.php.');
        }
        // Feature tables are included in the SQL installer. These best-effort
        // migrations support upgrades but must never make every page return 500
        // merely because a shared-host database user cannot run CREATE TABLE.
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            error_log('ShopNex site_settings migration: ' . $e->getMessage());
        }
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS staff_permissions (
                user_id INT NOT NULL, permission_key VARCHAR(60) NOT NULL, allowed TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY(user_id, permission_key)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            error_log('ShopNex staff_permissions migration: ' . $e->getMessage());
        }
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS product_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                image_path VARCHAR(500) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product_images_product_sort (product_id, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            error_log('ShopNex product_images migration: ' . $e->getMessage());
        }
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_hash CHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_password_resets_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            error_log('ShopNex password_resets migration: ' . $e->getMessage());
        }
    }
    return $pdo;
}

function site_setting(string $key, string $default = ''): string {
    try {
        $stmt = db()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string)$value;
    } catch (Throwable $e) {
        return $default;
    }
}

function save_site_setting(string $key, string $value): void {
    $stmt = db()->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute([$key, $value]);
}

function post_str(string $key, string $default = ''): string {
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url); exit; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid request token.'); } }
function flash(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function user(): ?array { return $_SESSION['user'] ?? null; }
function current_user(): ?array { return user(); }
function money($n): string { return '₹'.number_format((float)$n,2); }
function url(string $p=''): string { return rtrim(BASE_URL,'/').'/'.ltrim($p,'/'); }
function require_login(): void { if (!user()) redirect('login.php'); }
function require_role(string $role): void {
    if (!user() || (user()['role'] ?? '') !== $role) { http_response_code(403); exit('Access denied.'); }
    try {
        $st = db()->prepare('SELECT status, role FROM users WHERE id=? LIMIT 1');
        $st->execute([(int)user()['id']]);
        $row = $st->fetch();
        if (!$row || (int)$row['status'] !== 1 || $row['role'] !== $role) {
            $_SESSION = [];
            http_response_code(403);
            exit('Access denied. This account is inactive or its role has changed.');
        }
    } catch (Throwable $e) { http_response_code(503); exit('Account verification is temporarily unavailable.'); }
}

function staff_permission_keys(): array {
    return ['manage_site','products','inventory','orders','support','customers','coupons','reports','manage_staff'];
}
function user_can(string $permission): bool {
    if (!user()) return false;
    try { $active = db()->prepare('SELECT status FROM users WHERE id = ? LIMIT 1'); $active->execute([(int)user()['id']]); if ((int)$active->fetchColumn() !== 1) return false; } catch (Throwable $e) { return false; }
    if (user()['role'] === 'admin') return true;
    if (user()['role'] !== 'staff') return false;
    try {
        $stmt = db()->prepare('SELECT permission_key FROM staff_permissions WHERE user_id = ? AND allowed = 1');
        $stmt->execute([(int)user()['id']]);
        return in_array($permission, $stmt->fetchAll(PDO::FETCH_COLUMN), true);
    } catch (Throwable $e) { return false; }
}
function require_permission(string $permission): void {
    if (!user() || !in_array(user()['role'], ['admin','staff'], true) || !user_can($permission)) {
        http_response_code(403); exit('Access denied. Your account does not have permission for this section.');
    }
}
function require_staff_or_admin(): void {
    if (!user() || !in_array(user()['role'] ?? '', ['admin','staff'], true)) { http_response_code(403); exit('Access denied.'); }
    try {
        $st = db()->prepare('SELECT status, role FROM users WHERE id=? LIMIT 1');
        $st->execute([(int)user()['id']]);
        $row = $st->fetch();
        if (!$row || (int)$row['status'] !== 1 || $row['role'] !== user()['role']) {
            $_SESSION = [];
            http_response_code(403);
            exit('Access denied. This account is inactive or its role has changed.');
        }
    } catch (Throwable $e) { http_response_code(503); exit('Account verification is temporarily unavailable.'); }
}
function cart_count(): int { return array_sum($_SESSION['cart'] ?? []); }
