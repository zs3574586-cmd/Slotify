<?php
require __DIR__ . '/../src/bootstrap.php';

$p = $_GET['p'] ?? 'home';
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($post) csrf_check();
$pdo = db();

switch ($p) {

case 'home':
    $by = [];
    foreach (open_slots() as $s) $by[substr($s['starts_at'], 0, 10)][] = $s;
    view('home', ['days' => $by]);

case 'book':
    $id = (int)($_GET['slot'] ?? $_POST['slot'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM slots WHERE id = ?"); $st->execute([$id]);
    $slot = $st->fetch();
    if (!$slot || $slot['starts_at'] <= now()) { flash('That slot is no longer available.', 'err'); redirect(url('home')); }
    $old = ['name' => '', 'email' => '', 'note' => ''];
    if ($post) {
        $old = ['name' => trim($_POST['name'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'note' => trim($_POST['note'] ?? '')];
        if ($old['name'] === '' || mb_strlen($old['name']) > 100) $err = 'Please enter your name.';
        elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $err = 'Please enter a valid email.';
        elseif (mb_strlen($old['note']) > 500) $err = 'Note is too long (500 max).';
        else {
            $token = bin2hex(random_bytes(16));
            try {
                $pdo->exec('BEGIN IMMEDIATE');   // take the write lock before checking
                $chk = $pdo->prepare("SELECT 1 FROM bookings WHERE slot_id = ? AND status = 'confirmed'");
                $chk->execute([$id]);
                if ($chk->fetch()) throw new RuntimeException('taken');
                $pdo->prepare("INSERT INTO bookings (slot_id, name, email, note, token) VALUES (?,?,?,?,?)")
                    ->execute([$id, $old['name'], $old['email'], $old['note'], $token]);
                $pdo->exec('COMMIT');
                redirect(url('done', ['t' => $token]));
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->exec('ROLLBACK');
                // RuntimeException = our check; PDOException 23000 = unique index caught a race
                if ($e instanceof RuntimeException || ($e instanceof PDOException && $e->getCode() == 23000)) {
                    flash('Sorry, someone just booked that slot. Please pick another.', 'err'); redirect(url('home'));
                }
                throw $e;
            }
        }
    }
    view('book', ['slot' => $slot, 'old' => $old, 'err' => $err ?? null]);

case 'done':
    $st = $pdo->prepare("SELECT b.*, s.starts_at, s.ends_at FROM bookings b JOIN slots s ON s.id = b.slot_id WHERE b.token = ?");
    $st->execute([$_GET['t'] ?? '']);
    $b = $st->fetch();
    if (!$b) { http_response_code(404); view('notfound'); }
    view('done', ['b' => $b]);

case 'cancel':   // customer self-cancel via private token
    if (!$post) redirect(url('home'));
    $t = $_POST['t'] ?? '';
    $st = $pdo->prepare("SELECT s.starts_at FROM bookings b JOIN slots s ON s.id = b.slot_id WHERE b.token = ?");
    $st->execute([$t]);
    $row = $st->fetch();
    if ($row && $row['starts_at'] > now() && cancel_booking('token', $t)) flash('Your booking was cancelled.');
    else flash('This booking cannot be cancelled.', 'err');
    redirect(url('done', ['t' => $t]));

/* ---------------- Admin ---------------- */

case 'admin/login':
    if ($post) {
        if (hash_equals(cfg('admin_password'), $_POST['password'] ?? '')) {
            session_regenerate_id(true); $_SESSION['admin'] = true; redirect(url('admin'));
        }
        flash('Wrong password.', 'err'); redirect(url('admin/login'));
    }
    view('admin_login');

case 'admin/logout':
    $_SESSION = []; session_destroy(); redirect(url('home'));

case 'admin':
    require_admin();
    $stats = [
        'upcoming'  => $pdo->query("SELECT COUNT(*) FROM bookings b JOIN slots s ON s.id=b.slot_id WHERE b.status='confirmed' AND s.starts_at > '" . now() . "'")->fetchColumn(),
        'cancelled' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetchColumn(),
        'open'      => count(open_slots()),
    ];
    $bookings = $pdo->query("SELECT b.*, s.starts_at, s.ends_at FROM bookings b JOIN slots s ON s.id=b.slot_id ORDER BY s.starts_at DESC LIMIT 100")->fetchAll();
    $st = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM bookings b WHERE b.slot_id=s.id AND b.status='confirmed') AS taken
        FROM slots s WHERE s.starts_at > ? ORDER BY s.starts_at LIMIT 200");
    $st->execute([now()]);
    view('admin', ['stats' => $stats, 'bookings' => $bookings, 'slots' => $st->fetchAll()]);

case 'admin/slots':   // bulk-generate slots
    require_admin(); if (!$post) redirect(url('admin'));
    $d = $_POST['date'] ?? ''; $from = $_POST['from'] ?? ''; $to = $_POST['to'] ?? ''; $len = (int)($_POST['len'] ?? 0);
    $a = strtotime("$d $from"); $z = strtotime("$d $to");
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || !$a || !$z || $z <= $a || $len < 5 || $len > 480) {
        flash('Check the date, times and length.', 'err'); redirect(url('admin'));
    }
    $ins = $pdo->prepare("INSERT OR IGNORE INTO slots (starts_at, ends_at) VALUES (?, ?)");
    $n = 0;
    for ($t = $a; $t + $len * 60 <= $z; $t += $len * 60) {
        $ins->execute([date('Y-m-d H:i', $t), date('Y-m-d H:i', $t + $len * 60)]);
        $n += $ins->rowCount();
    }
    flash("$n slot(s) created."); redirect(url('admin'));

case 'admin/slot-delete':
    require_admin(); if (!$post) redirect(url('admin'));
    $st = $pdo->prepare("DELETE FROM slots WHERE id = ? AND NOT EXISTS (SELECT 1 FROM bookings WHERE slot_id = slots.id AND status='confirmed')");
    $st->execute([(int)$_POST['id']]);
    flash($st->rowCount() ? 'Slot deleted.' : 'Slot has a booking — cancel it first.', $st->rowCount() ? 'ok' : 'err');
    redirect(url('admin'));

case 'admin/booking-cancel':
    require_admin(); if (!$post) redirect(url('admin'));
    flash(cancel_booking('id', (int)$_POST['id']) ? 'Booking cancelled; the slot is open again.' : 'Nothing to cancel.');
    redirect(url('admin'));

default:
    http_response_code(404); view('notfound');
}
