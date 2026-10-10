<?php
require 'includes.php';
header('Content-Type: application/json');
collect();
[$ts, $now] = latest();
$base = strtoupper($_GET['base'] ?? 'USD');
if (!isset($now[$base])) $base = 'USD';
$period = max(60, (int)($_GET['period'] ?? 86400));

if (($_GET['mode'] ?? '') === 'history') {
    $cur = strtoupper($_GET['cur'] ?? 'EUR');
    $st = db()->prepare('SELECT a.ts, a.rate / b.rate FROM rates a JOIN rates b ON a.ts = b.ts AND b.cur = ?
                         WHERE a.cur = ? AND a.ts >= ? ORDER BY a.ts');
    $st->execute([$base, $cur, time() - $period]);
    echo json_encode(['base' => $base, 'cur' => $cur, 'points' => $st->fetchAll(PDO::FETCH_NUM)]);
    exit;
}

$old = past($period, $ts);
$rows = [];
foreach ($now as $c => $r) {
    $rate = $r / $now[$base];
    $chg = null;
    if (isset($old[$c], $old[$base])) {
        $o = $old[$c] / $old[$base];
        $chg = ($rate - $o) / $o * 100;
    }
    $rows[] = ['c' => $c, 'rate' => $rate, 'chg' => $chg];
}
echo json_encode(['updated' => $ts, 'base' => $base, 'rows' => $rows]);
