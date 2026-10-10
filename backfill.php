<?php
// One-time history import from the free Frankfurter API (ECB data, ~30 major currencies, no key).
// Run:  php backfill.php 90      (or open backfill.php?days=90 in the browser)
require 'includes.php';

$days = (int)($argv[1] ?? $_GET['days'] ?? 90);
$days = max(2, min($days, 1000));
$url = sprintf('https://api.frankfurter.dev/v1/%s..%s?base=USD', date('Y-m-d', strtotime("-$days days")), date('Y-m-d'));

$j = json_decode(@file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 20]])), true);
if (empty($j['rates'])) exit("Could not fetch history from Frankfurter.\n");

$db = db();
$exists = $db->prepare('SELECT 1 FROM rates WHERE ts = ? LIMIT 1');
$ins = $db->prepare('INSERT INTO rates VALUES(?,?,?)');
$db->beginTransaction();
$n = 0;
foreach ($j['rates'] as $date => $rates) {
    $ts = strtotime("$date 15:00:00 UTC"); // ECB publishes ~16:00 CET
    $exists->execute([$ts]);
    if ($exists->fetch()) continue;      // already imported
    $rates['USD'] = 1.0;
    foreach ($rates as $cur => $r) $ins->execute([$ts, $cur, $r]);
    $n++;
}
$db->commit();
echo "Imported $n days of history.\n";
