<?php
declare(strict_types=1);
$GLOBALS['config'] = require __DIR__ . '/../config.php';
date_default_timezone_set(cfg('timezone'));
session_start();

function cfg(string $k) { return $GLOBALS['config'][$k]; }

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        @mkdir(dirname(cfg('db')), 0775, true);
        $pdo = new PDO('sqlite:' . cfg('db'), null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
        $pdo->exec(file_get_contents(__DIR__ . '/../schema.sql'));
    }
    return $pdo;
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $p, array $q = []): string { return 'index.php?' . http_build_query(['p' => $p] + $q); }
function redirect(string $to): never { header('Location: ' . $to); exit; }
function flash(?string $msg = null, string $type = 'ok'): ?array {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function csrf_field(): string {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid form token. Go back and retry.'); }
}
function is_admin(): bool { return !empty($_SESSION['admin']); }
function require_admin(): void { if (!is_admin()) redirect(url('admin/login')); }
function fmt_date(string $dt): string { return date('D, j M Y', strtotime($dt)); }
function fmt_time(string $dt): string { return date('H:i', strtotime($dt)); }
function now(): string { return date('Y-m-d H:i'); }

function view(string $name, array $vars = [], string $title = ''): void {
    extract($vars);
    ob_start();
    require __DIR__ . "/../views/$name.php";
    $content = ob_get_clean();
    $flash = flash();
    require __DIR__ . '/../views/layout.php';
    exit;
}

function open_slots(): array {
    $st = db()->prepare("SELECT s.* FROM slots s WHERE s.starts_at > ?
        AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.slot_id = s.id AND b.status = 'confirmed')
        ORDER BY s.starts_at");
    $st->execute([now()]);
    return $st->fetchAll();
}

function cancel_booking(string $where, $val): bool {
    $st = db()->prepare("UPDATE bookings SET status='cancelled' WHERE status='confirmed' AND $where = ?");
    $st->execute([$val]);
    return $st->rowCount() > 0;
}
