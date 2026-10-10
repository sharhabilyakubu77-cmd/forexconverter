<?php
require 'includes.php'; collect();
$cur = htmlspecialchars(strtoupper($_GET['cur'] ?? 'EUR'));
$base = htmlspecialchars(strtoupper($_GET['base'] ?? 'USD'));
head("$base/$cur", 'rates'); ?>
    <div class="bar">
        <h2 style="margin:0"><?= $base ?> → <?= $cur ?></h2>
        <select id="period">
            <option value="3600">1 hour</option><option value="86400" selected>24 hours</option>
            <option value="604800">7 days</option><option value="2592000">30 days</option></select>
    </div>
    <div class="card"><div class="big" id="now">–</div><div id="chg"></div></div>
    <div class="card"><svg id="chart" viewBox="0 0 800 300" width="100%"></svg></div>
    <div class="grid">
        <div class="card"><div class="muted">Start</div><b id="s"></b></div>
        <div class="card"><div class="muted">High</div><b id="h"></b></div>
        <div class="card"><div class="muted">Low</div><b id="l"></b></div>
    </div>
    <script>
        const $ = id => document.getElementById(id);
        async function load() {
            const d = await (await fetch(`api.php?mode=history&cur=<?= $cur ?>&base=<?= $base ?>&period=${$('period').value}`)).json();
            const p = d.points; if (p.length < 2) { $('now').textContent = 'Collecting data…'; $('chg').textContent = 'Need at least 2 snapshots (run cron.php regularly).'; return; }
            const v = p.map(x => +x[1]), t = p.map(x => +x[0]);
            const min = Math.min(...v), max = Math.max(...v), span = (max - min) || 1;
            const x = i => 20 + i / (p.length - 1) * 760, y = val => 280 - (val - min) / span * 260;
            const up = v.at(-1) >= v[0], col = up ? '#22c55e' : '#ef4444';
            $('chart').innerHTML = `<polyline fill="none" stroke="${col}" stroke-width="2.5" points="${v.map((a,i)=>x(i)+','+y(a)).join(' ')}"/>`;
            const pct = (v.at(-1) - v[0]) / v[0] * 100;
            $('now').textContent = v.at(-1).toPrecision(6);
            $('chg').className = up ? 'up' : 'dn';
            $('chg').textContent = (up ? '▲ +' : '▼ ') + pct.toFixed(3) + '% over period';
            $('s').textContent = v[0].toPrecision(6); $('h').textContent = max.toPrecision(6); $('l').textContent = min.toPrecision(6);
        }
        $('period').onchange = load; load(); setInterval(load, 60000);
    </script>
<?php foot();