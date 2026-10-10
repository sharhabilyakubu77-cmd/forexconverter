<?php
require 'includes.php'; collect();
[$ts, $r] = latest(); ksort($r);
$from = strtoupper($_GET['from'] ?? 'USD'); $to = strtoupper($_GET['to'] ?? 'EUR');
$amt = (float)($_GET['amount'] ?? 1);
$out = (isset($r[$from], $r[$to])) ? $amt / $r[$from] * $r[$to] : null;
head('Converter', 'convert');
$opts = fn($sel) => implode('', array_map(fn($c) => "<option" . ($c === $sel ? ' selected' : '') . ">$c</option>", array_keys($r)));
?>
    <div class="card">
        <form class="bar" method="get">
            <input type="number" step="any" name="amount" value="<?= htmlspecialchars((string)$amt) ?>">
            <select name="from" id="from"><?= $opts($from) ?></select>
            <button type="button" id="swap" title="Swap">⇄</button>
            <select name="to" id="to"><?= $opts($to) ?></select>
            <button>Convert</button>
        </form>
        <?php if ($out !== null): ?>
            <div class="muted"><?= number_format($amt, 2) ?> <?= $from ?> =</div>
            <div class="big"><?= number_format($out, 4) ?> <?= $to ?></div>
            <p class="muted">1 <?= $from ?> = <?= round($r[$to] / $r[$from], 6) ?> <?= $to ?> ·
                <a style="color:var(--ac)" href="track.php?cur=<?= $to ?>&base=<?= $from ?>">see trend</a></p>
        <?php endif; ?>
    </div>
    <script>
        swap.onclick = () => { [from.value, to.value] = [to.value, from.value]; document.forms[0].submit(); };
    </script>
<?php foot();