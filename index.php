<?php require 'includes.php'; collect(); head('Live Rates', 'rates'); ?>
    <div class="bar">
        <label>Base <select id="base"><option>USD</option></select></label>
        <label>Change over <select id="period">
                <option value="3600">1 hour</option><option value="86400" selected>24 hours</option>
                <option value="604800">7 days</option><option value="2592000">30 days</option></select></label>
        <input id="q" placeholder="Search currency…">
        <span id="upd" class="muted"></span>
    </div>
    <table><thead><tr><th>Currency</th><th>Rate (1 <span id="b">USD</span>)</th><th>Change</th></tr></thead>
        <tbody id="rows"></tbody></table>
    <script>
        const $ = id => document.getElementById(id);
        let first = true;
        async function load() {
            const d = await (await fetch(`api.php?base=${$('base').value}&period=${$('period').value}`)).json();
            if (first) {
                $('base').innerHTML = d.rows.map(r => `<option ${r.c===d.base?'selected':''}>${r.c}</option>`).join('');
                first = false;
            }
            $('b').textContent = d.base;
            $('upd').textContent = 'Updated ' + new Date(d.updated * 1000).toLocaleString();
            $('rows').innerHTML = d.rows.filter(r => r.c !== d.base).map(r => {
                const c = r.chg, cls = c === null ? 'muted' : c >= 0 ? 'up' : 'dn';
                const txt = c === null ? 'collecting…' : (c >= 0 ? '▲ +' : '▼ ') + c.toFixed(3) + '%';
                return `<tr><td><a href="track.php?cur=${r.c}&base=${d.base}">${r.c}</a></td>
            <td>${r.rate.toPrecision(6)}</td><td class="${cls}">${txt}</td></tr>`;
            }).join('');
            filter();
        }
        function filter() {
            const q = $('q').value.toUpperCase();
            [...$('rows').rows].forEach(tr => tr.hidden = !tr.cells[0].textContent.includes(q));
        }
        $('base').onchange = $('period').onchange = load;
        $('q').oninput = filter;
        load(); setInterval(load, 60000);
    </script>
<?php foot();
