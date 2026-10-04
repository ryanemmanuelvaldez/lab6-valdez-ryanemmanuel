<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Console | LavaLust</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#18211f; --muted:#68736f; --paper:#f4f6f1; --panel:#fff; --line:#dce3dc; --green:#1f6b58; --green-dark:#164a3e; --mint:#dcefe6; --orange:#c75b31; --red:#aa3e3e; --shadow:0 18px 50px rgba(35,61,50,.09); --mono:'DM Mono',monospace; --sans:'Space Grotesk',sans-serif; }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { margin:0; min-height:100vh; color:var(--ink); background:var(--paper); font-family:var(--sans); background-image:linear-gradient(135deg,rgba(255,255,255,.65),transparent 45%),linear-gradient(90deg,rgba(31,107,88,.045) 1px,transparent 1px),linear-gradient(rgba(31,107,88,.045) 1px,transparent 1px); background-size:auto,36px 36px,36px 36px; }
        .shell { width:min(1180px,calc(100% - 40px)); margin:0 auto; }
        .topbar { display:flex; align-items:center; justify-content:space-between; padding:28px 0; border-bottom:1px solid var(--line); }
        .brand { display:flex; align-items:center; gap:12px; font-weight:700; letter-spacing:-.03em; }
        .brand-mark { display:grid; place-items:center; width:38px; height:38px; color:#fff; background:var(--green); border-radius:11px; box-shadow:6px 6px 0 #b9d6c8; }
        .brand-mark svg { width:20px; height:20px; }
        .brand small { display:block; color:var(--muted); font:11px var(--mono); letter-spacing:.08em; text-transform:uppercase; }
        .connection { display:flex; align-items:center; gap:9px; color:var(--muted); font:12px var(--mono); }
        .connection-dot { width:9px; height:9px; background:#38a169; border-radius:50%; box-shadow:0 0 0 5px rgba(56,161,105,.12); }
        .hero { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(300px,.75fr); gap:60px; padding:78px 0 58px; align-items:end; }
        .eyebrow { margin:0 0 17px; color:var(--orange); font:500 12px var(--mono); letter-spacing:.12em; text-transform:uppercase; }
        h1 { max-width:680px; margin:0; font-size:clamp(42px,6vw,78px); line-height:.96; letter-spacing:-.075em; }
        .hero-copy { max-width:520px; margin:24px 0 0; color:var(--muted); font-size:17px; line-height:1.65; }
        .hero-note { padding:20px; border:1px solid var(--line); border-left:4px solid var(--green); background:rgba(255,255,255,.68); box-shadow:var(--shadow); }
        .hero-note strong { display:block; margin-bottom:9px; font-size:14px; }
        .hero-note code { color:var(--green-dark); font:12px/1.8 var(--mono); }
        .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:46px; }
        .stat { padding:22px 24px; border:1px solid var(--line); background:var(--panel); box-shadow:var(--shadow); }
        .stat-label { color:var(--muted); font:11px var(--mono); letter-spacing:.1em; text-transform:uppercase; }
        .stat-value { margin-top:12px; font-size:35px; font-weight:600; letter-spacing:-.06em; }
        .stat-value.applied { color:var(--green); } .stat-value.pending { color:var(--orange); }
        .workspace { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(310px,.65fr); gap:18px; padding-bottom:80px; }
        .panel { border:1px solid var(--line); background:var(--panel); box-shadow:var(--shadow); }
        .panel-head { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:21px 24px; border-bottom:1px solid var(--line); }
        .panel-head h2 { margin:0; font-size:16px; letter-spacing:-.03em; } .panel-head span { color:var(--muted); font:11px var(--mono); }
        .table-wrap { overflow-x:auto; } table { width:100%; border-collapse:collapse; min-width:560px; }
        th,td { padding:17px 24px; text-align:left; border-bottom:1px solid #edf0ec; font-size:13px; } th { color:var(--muted); font:11px var(--mono); letter-spacing:.08em; text-transform:uppercase; background:#fbfcfa; }
        td:first-child,td:nth-child(2) { font-family:var(--mono); } td:first-child { color:var(--muted); } td:nth-child(2) { font-size:12px; }
        .status { display:inline-flex; align-items:center; gap:7px; color:var(--green); font:11px var(--mono); } .status::before { content:''; width:7px; height:7px; background:currentColor; border-radius:50%; } .status.pending { color:var(--orange); }
        .empty { padding:35px 24px; color:var(--muted); font-size:14px; }
        .actions { padding:10px; }
        .action { display:flex; align-items:center; justify-content:space-between; width:100%; padding:16px 14px; border:0; border-bottom:1px solid var(--line); color:var(--ink); background:transparent; text-align:left; font:500 14px var(--sans); cursor:pointer; transition:background .2s,padding .2s; }
        .action:last-child { border-bottom:0; } .action:hover { padding-left:19px; background:var(--mint); } .action span { color:var(--muted); font:11px var(--mono); } .action.danger:hover { color:var(--red); background:#faeeee; }
        .terminal { min-height:152px; margin:0 10px 10px; padding:16px; color:#d9eee4; background:#162823; font:11px/1.7 var(--mono); white-space:pre-wrap; overflow:auto; } .terminal::before { content:'$ migration console'; display:block; margin-bottom:8px; color:#78bba1; }
        .refresh { border:1px solid var(--line); padding:9px 12px; color:var(--green); background:#fff; font:11px var(--mono); cursor:pointer; } .refresh:hover { background:var(--mint); }
        footer { display:flex; justify-content:space-between; padding:22px 0 32px; border-top:1px solid var(--line); color:var(--muted); font:11px var(--mono); }
        @keyframes rise { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } } .hero,.stats,.workspace { animation:rise .55s ease both; } .stats { animation-delay:.08s; } .workspace { animation-delay:.16s; }
        @media (max-width:800px) { .shell { width:min(100% - 28px,620px); } .hero,.workspace { grid-template-columns:1fr; gap:28px; } .hero { padding-top:52px; } }
        @media (max-width:520px) { .topbar { align-items:flex-start; gap:18px; } .connection { font-size:10px; } .stats { gap:8px; } .stat { padding:16px 12px; } .stat-value { font-size:28px; } .panel-head,th,td { padding-left:16px; padding-right:16px; } footer { display:block; line-height:2; } }
    </style>
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <div class="brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3c1.5 3.7 5 4.8 5 9a5 5 0 0 1-10 0c0-2.2 1.3-4.1 3.2-5.8.1 2.2 1 3.2 1.8 3.8C13.2 8.2 12.6 5.5 12 3Z"/><path d="M9.5 15.5a2.7 2.7 0 0 0 5 0"/></svg></span><span>LavaLust <small>migration console</small></span></div>
            <div class="connection"><span class="connection-dot"></span> API connected</div>
        </header>
        <section class="hero"><div><p class="eyebrow">Schema operations / 01</p><h1>Keep your database moving.</h1><p class="hero-copy">A quiet control room for the migration history behind your LavaLust application. Inspect the current state, apply changes, and keep the schema versioned.</p></div><aside class="hero-note"><strong>Current pipeline</strong><code>CLI → Controller → Route<br>→ Migration Library → Database</code></aside></section>
        <section class="stats" aria-label="Migration summary"><article class="stat"><div class="stat-label">Applied</div><div class="stat-value applied" id="applied-count">—</div></article><article class="stat"><div class="stat-label">Pending</div><div class="stat-value pending" id="pending-count">—</div></article><article class="stat"><div class="stat-label">Last check</div><div class="stat-value" id="checked-at">—</div></article></section>
        <section class="workspace">
            <article class="panel"><div class="panel-head"><h2>Migration ledger</h2><button class="refresh" id="refresh-status" type="button">Refresh status</button></div><div class="table-wrap"><table><thead><tr><th>Version</th><th>Migration file</th><th>State</th></tr></thead><tbody id="migration-rows"><tr><td colspan="3" class="empty">Reading migration status…</td></tr></tbody></table></div></article>
            <aside class="panel"><div class="panel-head"><h2>Operations</h2><span>LIVE ROUTES</span></div><div class="actions"><button class="action" type="button" data-route="migrate">Run pending migrations <span>migrate ↗</span></button><button class="action" type="button" data-route="create-migration/create_products_table">Create products migration <span>create ↗</span></button><button class="action" type="button" data-route="rollback">Rollback latest <span>rollback ↗</span></button><button class="action danger" type="button" data-route="refresh" data-confirm="Refresh will roll back and recreate all migrations. Continue?">Refresh database <span>refresh ↗</span></button></div><div class="terminal" id="operation-output">Ready. Choose an operation to call the backend.</div></aside>
        </section>
        <footer><span>LavaLust / database tools</span><span>local development</span></footer>
    </main>
    <script>
        const rows = document.getElementById('migration-rows'); const output = document.getElementById('operation-output'); const appliedCount = document.getElementById('applied-count'); const pendingCount = document.getElementById('pending-count'); const checkedAt = document.getElementById('checked-at');
        function parseStatus(html) { const lines = html.replace(/<[^>]*>/g, '').split('\n').map(line => line.trim()).filter(Boolean); return lines.filter(line => /^\d+\s+\S+/.test(line)).map(line => { const match = line.match(/^(\d+)\s+(.+?\.php)\s+(APPLIED|PENDING)$/); return match ? { version:match[1], file:match[2], state:match[3] } : null; }).filter(Boolean); }
        async function loadStatus() { rows.innerHTML = '<tr><td colspan="3" class="empty">Reading migration status…</td></tr>'; try { const response = await fetch('status', { cache:'no-store' }); const migrations = parseStatus(await response.text()); const applied = migrations.filter(item => item.state === 'APPLIED').length; appliedCount.textContent = applied; pendingCount.textContent = migrations.length - applied; checkedAt.textContent = new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' }); rows.innerHTML = migrations.length ? migrations.map(item => `<tr><td>${item.version}</td><td>${item.file}</td><td><span class="status ${item.state === 'PENDING' ? 'pending' : ''}">${item.state}</span></td></tr>`).join('') : '<tr><td colspan="3" class="empty">No migration files found.</td></tr>'; } catch (error) { rows.innerHTML = '<tr><td colspan="3" class="empty">Could not reach the migration status route.</td></tr>'; output.textContent = error.message; } }
        async function runOperation(button) { const route = button.dataset.route; if (button.dataset.confirm && !window.confirm(button.dataset.confirm)) return; output.textContent = `Calling /${route}…`; try { const response = await fetch(route, { cache:'no-store' }); const text = (await response.text()).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim(); output.textContent = text || `Request completed with status ${response.status}.`; await loadStatus(); } catch (error) { output.textContent = `Request failed: ${error.message}`; } }
        document.getElementById('refresh-status').addEventListener('click', loadStatus); document.querySelectorAll('[data-route]').forEach(button => button.addEventListener('click', () => runOperation(button))); loadStatus();
    </script>
</body>
</html>
