<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin workspace | LavaLust</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#202a26; --muted:#718078; --paper:#f5f7f3; --white:#fff; --line:#e2e8e1; --green:#276b52; --green-dark:#194b3a; --mint:#e8f2ec; --orange:#c9693e; --red:#ae4e4b; --mono:'DM Mono',monospace; --sans:'Manrope',sans-serif; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; color:var(--ink); background:var(--paper); font:14px var(--sans); }
        button,input,select { font:inherit; }
        button { cursor:pointer; }
        .app { display:grid; grid-template-columns:242px minmax(0,1fr); min-height:100vh; }
        .sidebar { display:flex; flex-direction:column; padding:25px 16px 18px; color:#edf5ef; background:#203c32; }
        .brand { display:flex; align-items:center; gap:11px; padding:0 8px 30px; font-size:16px; font-weight:800; }
        .brand-mark { display:grid; place-items:center; width:34px; height:34px; border:1px solid #658274; border-radius:9px; color:#dceee2; background:#315647; font:500 15px var(--mono); }
        .brand small { display:block; margin-top:2px; color:#a9c0b2; font:10px var(--mono); letter-spacing:.08em; text-transform:uppercase; }
        .nav-label { padding:0 11px 9px; color:#91aa9c; font:10px var(--mono); letter-spacing:.12em; text-transform:uppercase; }
        .nav { display:grid; gap:4px; }
        .nav button { display:flex; align-items:center; gap:12px; width:100%; padding:11px 12px; border:0; border-radius:6px; color:#c0d0c6; background:transparent; text-align:left; font-weight:600; }
        .nav button:hover,.nav button[aria-current="page"] { color:#fff; background:#315647; }
        .nav svg { width:17px; height:17px; flex:none; }
        .side-bottom { margin-top:auto; }
        .demo-note { margin:20px 4px; padding:13px; border:1px solid #486456; border-radius:6px; color:#d0ded5; background:#29483b; font-size:11px; line-height:1.6; }
        .demo-note strong { display:block; margin-bottom:4px; color:#fff; font:11px var(--mono); text-transform:uppercase; }
        .profile { display:flex; align-items:center; gap:10px; padding:16px 8px 0; border-top:1px solid #405a4d; }
        .avatar { display:grid; place-items:center; width:34px; height:34px; flex:none; border-radius:50%; color:#284b3b; background:#d4e7d9; font-size:11px; font-weight:800; }
        .profile-name { font-size:12px; font-weight:700; }.profile-role { margin-top:2px; color:#a9c0b2; font:10px var(--mono); }
        .main { min-width:0; }
        .topbar { display:flex; align-items:center; justify-content:space-between; min-height:74px; padding:0 38px; border-bottom:1px solid var(--line); background:rgba(255,255,255,.8); }
        .crumb { color:var(--muted); font-size:12px; }.crumb strong { color:var(--ink); }
        .top-actions { display:flex; align-items:center; gap:13px; }
        .local-pill { display:flex; align-items:center; gap:7px; color:var(--muted); font:10px var(--mono); text-transform:uppercase; letter-spacing:.05em; }
        .local-pill::before { width:7px; height:7px; border-radius:50%; background:#d48b42; content:''; }
        .content { width:min(1280px,100%); margin:0 auto; padding:38px; }
        .page-head { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; margin-bottom:27px; }
        .eyebrow { margin:0 0 8px; color:var(--orange); font:10px var(--mono); letter-spacing:.12em; text-transform:uppercase; }
        h1 { margin:0; font-size:29px; line-height:1.2; letter-spacing:0; }
        .subtitle { margin:8px 0 0; color:var(--muted); font-size:13px; }
        .button { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:39px; padding:0 14px; border:1px solid var(--line); border-radius:5px; color:var(--ink); background:#fff; font-size:12px; font-weight:700; }
        .button:hover { background:#f1f5f0; }.button.primary { border-color:var(--green); color:#fff; background:var(--green); }.button.primary:hover { background:var(--green-dark); }
        .button svg { width:15px; height:15px; }
        .view { display:none; }.view.active { display:block; animation:appear .24s ease-out; }
        .stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:13px; margin-bottom:25px; }
        .stat { padding:18px 19px; border:1px solid var(--line); border-radius:6px; background:var(--white); }
        .stat-top { display:flex; align-items:center; justify-content:space-between; color:var(--muted); font-size:11px; font-weight:600; }
        .stat-icon { display:grid; place-items:center; width:29px; height:29px; border-radius:6px; color:var(--green); background:var(--mint); font:12px var(--mono); }
        .stat-value { margin-top:12px; font-size:26px; font-weight:800; letter-spacing:0; }
        .stat-foot { margin-top:4px; color:var(--muted); font-size:10px; }
        .columns { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(260px,.8fr); gap:16px; }
        .panel { border:1px solid var(--line); border-radius:6px; background:var(--white); }
        .panel-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:17px 19px; border-bottom:1px solid var(--line); }
        .panel-head h2 { margin:0; font-size:13px; }.panel-head a { color:var(--green); font-size:11px; font-weight:700; text-decoration:none; }
        .panel-body { padding:17px 19px; }
        .activity { display:grid; gap:16px; }.activity-row { display:flex; align-items:flex-start; gap:11px; }.activity-dot { width:8px; height:8px; margin-top:5px; border-radius:50%; background:var(--green); box-shadow:0 0 0 4px var(--mint); }
        .activity-row p { margin:0; font-size:11px; line-height:1.55; }.activity-row small { display:block; margin-top:3px; color:var(--muted); font:10px var(--mono); }
        .role-list { display:grid; gap:13px; }.role-line { display:flex; align-items:center; justify-content:space-between; font-size:11px; }.role-line span:last-child { color:var(--muted); font:10px var(--mono); }
        .toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:11px; padding:14px 16px; border-bottom:1px solid var(--line); }
        .filters { display:flex; flex-wrap:wrap; gap:8px; }
        .search { position:relative; width:min(280px,100%); }.search svg { position:absolute; top:11px; left:11px; width:15px; height:15px; color:#8a978f; }
        .search input,.filters select,.field input,.field select { width:100%; min-height:37px; border:1px solid var(--line); border-radius:5px; outline:none; color:var(--ink); background:#fff; }
        .search input { padding:0 12px 0 34px; font-size:11px; }.filters select { width:auto; min-width:122px; padding:0 28px 0 10px; font-size:11px; }
        .search input:focus,.filters select:focus,.field input:focus,.field select:focus { border-color:#6f9a82; box-shadow:0 0 0 3px rgba(39,107,82,.1); }
        .table-wrap { overflow-x:auto; } table { width:100%; min-width:650px; border-collapse:collapse; }
        th,td { padding:13px 16px; border-bottom:1px solid #edf0ec; text-align:left; } th { color:var(--muted); background:#fbfcfa; font:9px var(--mono); letter-spacing:.08em; text-transform:uppercase; }
        td { font-size:11px; } tbody tr:last-child td { border-bottom:0; }.user-cell { display:flex; align-items:center; gap:10px; min-width:155px; }.user-cell .avatar { width:30px; height:30px; font-size:10px; }.user-name { font-weight:700; }.user-email { margin-top:3px; color:var(--muted); font-size:10px; }
        .role-badge,.status-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 8px; border-radius:4px; font:9px var(--mono); }.role-badge { color:#426c55; background:#edf5ef; text-transform:capitalize; }.role-badge.admin { color:#a34e30; background:#fff1e9; }.status-badge { color:#347250; background:#edf6ef; }.status-badge.inactive { color:#8a7771; background:#f3f0ee; }
        .row-actions { display:flex; justify-content:flex-end; gap:5px; }.icon-button { display:grid; place-items:center; width:29px; height:29px; border:1px solid transparent; border-radius:4px; color:#68776e; background:transparent; }.icon-button:hover { border-color:var(--line); background:#f5f7f4; }.icon-button.delete:hover { color:var(--red); background:#fff0ee; }.icon-button svg { width:14px; height:14px; }
        .empty { padding:35px 16px; color:var(--muted); text-align:center; }
        .pagination { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-top:1px solid var(--line); color:var(--muted); font:10px var(--mono); }
        .role-cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }.role-card { padding:20px; border:1px solid var(--line); border-radius:6px; background:#fff; }.role-card h2 { margin:0 0 8px; font-size:14px; }.role-card p { min-height:38px; margin:0; color:var(--muted); font-size:11px; line-height:1.6; }.role-card strong { display:block; margin-top:19px; font:22px var(--mono); }.role-card small { color:var(--muted); font-size:10px; }
        .modal-backdrop { position:fixed; inset:0; z-index:5; display:none; place-items:center; padding:18px; background:rgba(22,36,29,.48); }.modal-backdrop.open { display:grid; }
        .modal { width:min(440px,100%); border-radius:7px; background:#fff; box-shadow:0 25px 80px rgba(10,30,20,.2); animation:appear .2s ease-out; }.modal-head { display:flex; align-items:center; justify-content:space-between; padding:20px 22px; border-bottom:1px solid var(--line); }.modal-head h2 { margin:0; font-size:16px; }.close { border:0; color:var(--muted); background:none; font-size:21px; }
        .form { display:grid; gap:14px; padding:20px 22px 22px; }.field label { display:block; margin-bottom:6px; font-size:11px; font-weight:700; }.field input,.field select { padding:0 10px; font-size:12px; }.form-error { display:none; color:var(--red); font-size:11px; }.form-error.show { display:block; }.form-actions { display:flex; justify-content:flex-end; gap:8px; padding-top:5px; }
        .toast { position:fixed; right:22px; bottom:22px; z-index:8; padding:12px 16px; border-radius:5px; color:white; background:#203c32; box-shadow:0 8px 30px #203c3230; font-size:12px; opacity:0; transform:translateY(8px); pointer-events:none; transition:.2s; }.toast.show { opacity:1; transform:translateY(0); }
        @keyframes appear { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
        @media (max-width:980px) { .app { grid-template-columns:205px minmax(0,1fr); }.content { padding:28px 22px; }.topbar { padding:0 22px; }.stats { grid-template-columns:repeat(2,minmax(0,1fr)); }.columns { grid-template-columns:1fr; } }
        @media (max-width:680px) { .app { display:block; }.sidebar { position:sticky; top:0; z-index:3; flex-direction:row; align-items:center; justify-content:space-between; padding:10px 14px; }.brand { padding:0; }.brand-mark { width:31px; height:31px; }.nav-label,.side-bottom { display:none; }.nav { display:flex; gap:3px; }.nav button { width:auto; padding:9px; font-size:0; }.nav svg { width:19px; height:19px; }.main { min-height:calc(100vh - 53px); }.topbar { min-height:52px; padding:0 16px; }.content { padding:25px 15px; }.page-head { align-items:flex-start; flex-direction:column; }.page-head .button { width:100%; }.stats { gap:9px; }.stat { padding:14px; }.stat-value { font-size:23px; }.role-cards { grid-template-columns:1fr; }.role-card p { min-height:0; }.search { width:100%; }.toolbar { align-items:stretch; flex-direction:column; }.filters select { flex:1; }.crumb { font-size:10px; }.local-pill { font-size:9px; } }
        @media (prefers-reduced-motion:reduce) { *,*::before,*::after { animation-duration:.01ms !important; transition-duration:.01ms !important; scroll-behavior:auto !important; } }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><span class="brand-mark">L</span><span>LavaLust<small>Admin workspace</small></span></div>
        <div class="nav-label">Workspace</div>
        <nav class="nav" aria-label="Main navigation">
            <button type="button" data-view="overview" aria-current="page"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="5" rx="1"/><rect x="13" y="10" width="8" height="11" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/></svg>Overview</button>
            <button type="button" data-view="users"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20v-1.5A4.5 4.5 0 0 1 7 14h4a4.5 4.5 0 0 1 4.5 4.5V20M16 4.8a3.5 3.5 0 0 1 0 6.4M17 14h1a4 4 0 0 1 4 4v2"/></svg>Users</button>
            <button type="button" data-view="roles"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 20 6v5c0 5-3.4 8.1-8 10-4.6-1.9-8-5-8-10V6l8-3Z"/><path d="m9 12 2 2 4-4"/></svg>Roles</button>
        </nav>
        <div class="side-bottom">
            <div class="demo-note"><strong>Local preview</strong>Changes are saved in this browser only. Connect an API before using real accounts.</div>
            <div class="profile"><span class="avatar">AD</span><span><span class="profile-name">Workspace Admin</span><span class="profile-role">ADMINISTRATOR</span></span></div>
        </div>
    </aside>
    <main class="main">
        <header class="topbar"><div class="crumb">Workspace <span aria-hidden="true">/</span> <strong id="breadcrumb">Overview</strong></div><div class="top-actions"><span class="local-pill">Local preview</span></div></header>
        <div class="content">
            <section class="view active" id="view-overview">
                <div class="page-head"><div><p class="eyebrow">Administration / 01</p><h1>Good day, Admin.</h1><p class="subtitle">A clear view of your people and workspace.</p></div><button class="button primary" type="button" data-open-modal><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Add user</button></div>
                <div class="stats" aria-label="User summary">
                    <article class="stat"><div class="stat-top">Total users<span class="stat-icon">01</span></div><div class="stat-value" id="total-count">0</div><div class="stat-foot">Across all workspace roles</div></article>
                    <article class="stat"><div class="stat-top">Administrators<span class="stat-icon">02</span></div><div class="stat-value" id="admin-count">0</div><div class="stat-foot">Full workspace access</div></article>
                    <article class="stat"><div class="stat-top">Standard users<span class="stat-icon">03</span></div><div class="stat-value" id="user-count">0</div><div class="stat-foot">Active user accounts</div></article>
                    <article class="stat"><div class="stat-top">Inactive<span class="stat-icon">04</span></div><div class="stat-value" id="inactive-count">0</div><div class="stat-foot">Accounts currently paused</div></article>
                </div>
                <div class="columns">
                    <article class="panel"><div class="panel-head"><h2>Recently added</h2><a href="#users" data-go-users>View all users</a></div><div class="table-wrap"><table><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead><tbody id="recent-rows"></tbody></table></div></article>
                    <article class="panel"><div class="panel-head"><h2>Workspace roles</h2><a href="#roles" data-go-roles>Manage roles</a></div><div class="panel-body role-list"><div class="role-line"><span><span class="role-badge admin">Admin</span> Full access</span><span id="role-admin-count">0 users</span></div><div class="role-line"><span><span class="role-badge">Moderator</span> Manage content</span><span id="role-mod-count">0 users</span></div><div class="role-line"><span><span class="role-badge">User</span> Standard access</span><span id="role-user-count">0 users</span></div></div></article>
                </div>
            </section>
            <section class="view" id="view-users">
                <div class="page-head"><div><p class="eyebrow">People / 02</p><h1>User management</h1><p class="subtitle">Manage workspace accounts, roles, and access.</p></div><button class="button primary" type="button" data-open-modal><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Add user</button></div>
                <article class="panel"><div class="toolbar"><label class="search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg><input id="search-users" type="search" placeholder="Search name or email" autocomplete="off" aria-label="Search users"></label><div class="filters"><select id="role-filter" aria-label="Filter by role"><option value="all">All roles</option><option value="admin">Admin</option><option value="moderator">Moderator</option><option value="user">User</option></select><select id="status-filter" aria-label="Filter by status"><option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></div></div><div class="table-wrap"><table><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th><span class="sr-only">Actions</span></th></tr></thead><tbody id="user-rows"></tbody></table></div><div class="pagination"><span id="result-count">0 users</span><span>LOCAL DATA</span></div></article>
            </section>
            <section class="view" id="view-roles">
                <div class="page-head"><div><p class="eyebrow">Access / 03</p><h1>Roles & permissions</h1><p class="subtitle">Role options match the roles currently supported by the users schema.</p></div></div>
                <div class="role-cards"><article class="role-card"><h2><span class="role-badge admin">Admin</span></h2><p>Full access to workspace users and administration.</p><strong id="card-admin-count">0</strong><small>accounts</small></article><article class="role-card"><h2><span class="role-badge">Moderator</span></h2><p>Moderate workspace content and support user activity.</p><strong id="card-mod-count">0</strong><small>accounts</small></article><article class="role-card"><h2><span class="role-badge">User</span></h2><p>Standard access for members of the workspace.</p><strong id="card-user-count">0</strong><small>accounts</small></article></div>
            </section>
        </div>
    </main>
</div>
<div class="modal-backdrop" id="user-modal" role="presentation"><section class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title"><header class="modal-head"><h2 id="modal-title">Add workspace user</h2><button class="close" type="button" aria-label="Close dialog" data-close-modal>&times;</button></header><form class="form" id="user-form"><div class="field"><label for="username">Username</label><input id="username" name="username" maxlength="100" required autocomplete="off" placeholder="e.g. alex.rivera"></div><div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="255" required placeholder="alex@example.com"></div><div class="field"><label for="role">Role</label><select id="role" name="role"><option value="user">User</option><option value="moderator">Moderator</option><option value="admin">Admin</option></select></div><div class="form-error" id="form-error" role="alert"></div><div class="form-actions"><button class="button" type="button" data-close-modal>Cancel</button><button class="button primary" type="submit">Add user</button></div></form></section></div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>
    const storageKey = 'lavalust-admin-users-v1';
    const seedUsers = [
        { id:'u-1001', username:'maya.chen', email:'maya.chen@example.com', role:'admin', is_active:true, created_at:'2026-09-28' },
        { id:'u-1002', username:'noah.reyes', email:'noah.reyes@example.com', role:'user', is_active:true, created_at:'2026-09-25' },
        { id:'u-1003', username:'ella.santos', email:'ella.santos@example.com', role:'moderator', is_active:true, created_at:'2026-09-22' },
        { id:'u-1004', username:'liam.patel', email:'liam.patel@example.com', role:'user', is_active:false, created_at:'2026-09-18' },
        { id:'u-1005', username:'zoe.martin', email:'zoe.martin@example.com', role:'user', is_active:true, created_at:'2026-09-14' }
    ];
    const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[char]);
    function loadUsers() {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey));
            return Array.isArray(saved) ? saved : seedUsers;
        } catch (error) { return seedUsers; }
    }
    let users = loadUsers();
    function saveUsers() {
        try { localStorage.setItem(storageKey, JSON.stringify(users)); }
        catch (error) { showToast('Browser storage is unavailable; changes may not persist.'); }
    }
    function formatDate(value) {
        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString(undefined, { month:'short', day:'numeric', year:'numeric' });
    }
    function initials(username) { return username.split(/[. _-]+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase(); }
    function userMarkup(user, includeActions) {
        const username = escapeHtml(user.username);
        const role = ['admin','moderator','user'].includes(user.role) ? user.role : 'user';
        const active = Boolean(user.is_active);
        const roleName = role === 'admin' ? 'Admin' : role.charAt(0).toUpperCase() + role.slice(1);
        const actions = includeActions ? `<td><div class="row-actions"><button class="icon-button" type="button" data-toggle="${escapeHtml(user.id)}" aria-label="${active ? 'Deactivate' : 'Activate'} ${username}" title="${active ? 'Deactivate' : 'Activate'} user"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v9M6.2 5.8a8 8 0 1 0 11.6 0"/></svg></button><button class="icon-button delete" type="button" data-delete="${escapeHtml(user.id)}" aria-label="Remove ${username}" title="Remove user"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg></button></div></td>` : '';
        return `<tr><td><div class="user-cell"><span class="avatar">${escapeHtml(initials(user.username))}</span><span><span class="user-name">${username}</span><span class="user-email">${escapeHtml(user.email)}</span></span></div></td><td><span class="role-badge ${role === 'admin' ? 'admin' : ''}">${roleName}</span></td><td><span class="status-badge ${active ? '' : 'inactive'}">${active ? 'Active' : 'Inactive'}</span></td><td>${formatDate(user.created_at)}</td>${actions}</tr>`;
    }
    function render() {
        const admins = users.filter(user => user.role === 'admin').length;
        const moderators = users.filter(user => user.role === 'moderator').length;
        const standard = users.filter(user => user.role === 'user').length;
        document.getElementById('total-count').textContent = users.length;
        document.getElementById('admin-count').textContent = admins;
        document.getElementById('user-count').textContent = standard;
        document.getElementById('inactive-count').textContent = users.filter(user => !user.is_active).length;
        document.getElementById('role-admin-count').textContent = `${admins} users`;
        document.getElementById('role-mod-count').textContent = `${moderators} users`;
        document.getElementById('role-user-count').textContent = `${standard} users`;
        document.getElementById('card-admin-count').textContent = admins;
        document.getElementById('card-mod-count').textContent = moderators;
        document.getElementById('card-user-count').textContent = standard;
        const sorted = [...users].sort((a, b) => b.created_at.localeCompare(a.created_at));
        document.getElementById('recent-rows').innerHTML = sorted.length ? sorted.slice(0, 4).map(user => userMarkup(user, false)).join('') : '<tr><td class="empty" colspan="4">No users have been added yet.</td></tr>';
        const query = document.getElementById('search-users').value.trim().toLowerCase();
        const roleFilter = document.getElementById('role-filter').value;
        const statusFilter = document.getElementById('status-filter').value;
        const filtered = sorted.filter(user => `${user.username} ${user.email}`.toLowerCase().includes(query) && (roleFilter === 'all' || user.role === roleFilter) && (statusFilter === 'all' || (statusFilter === 'active') === Boolean(user.is_active)));
        document.getElementById('user-rows').innerHTML = filtered.length ? filtered.map(user => userMarkup(user, true)).join('') : '<tr><td class="empty" colspan="5">No users match these filters.</td></tr>';
        document.getElementById('result-count').textContent = `${filtered.length} of ${users.length} users`;
    }
    function showToast(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('show');
        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => toast.classList.remove('show'), 2600);
    }
    function setView(name) {
        document.querySelectorAll('.view').forEach(view => view.classList.toggle('active', view.id === `view-${name}`));
        document.querySelectorAll('.nav button[data-view]').forEach(button => {
            if (button.dataset.view === name) button.setAttribute('aria-current', 'page');
            else button.removeAttribute('aria-current');
        });
        document.getElementById('breadcrumb').textContent = name.charAt(0).toUpperCase() + name.slice(1);
    }
    function openModal() {
        document.getElementById('user-modal').classList.add('open');
        document.getElementById('username').focus();
    }
    function closeModal() {
        document.getElementById('user-modal').classList.remove('open');
        document.getElementById('user-form').reset();
        document.getElementById('form-error').classList.remove('show');
    }
    document.querySelectorAll('.nav button[data-view]').forEach(button => button.addEventListener('click', () => setView(button.dataset.view)));
    document.querySelectorAll('[data-go-users]').forEach(link => link.addEventListener('click', event => { event.preventDefault(); setView('users'); }));
    document.querySelectorAll('[data-go-roles]').forEach(link => link.addEventListener('click', event => { event.preventDefault(); setView('roles'); }));
    document.querySelectorAll('[data-open-modal]').forEach(button => button.addEventListener('click', openModal));
    document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', closeModal));
    document.getElementById('user-modal').addEventListener('click', event => { if (event.target.id === 'user-modal') closeModal(); });
    document.getElementById('search-users').addEventListener('input', render);
    document.getElementById('role-filter').addEventListener('change', render);
    document.getElementById('status-filter').addEventListener('change', render);
    document.getElementById('user-form').addEventListener('submit', event => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const username = String(form.get('username')).trim();
        const email = String(form.get('email')).trim().toLowerCase();
        const error = document.getElementById('form-error');
        if (users.some(user => user.username.toLowerCase() === username.toLowerCase() || user.email.toLowerCase() === email)) {
            error.textContent = 'That username or email is already in this local list.';
            error.classList.add('show');
            return;
        }
        users.unshift({ id:`u-${Date.now()}`, username, email, role:String(form.get('role')), is_active:true, created_at:new Date().toISOString().slice(0, 10) });
        saveUsers();
        render();
        closeModal();
        setView('users');
        showToast('User added to this browser preview.');
    });
    document.getElementById('user-rows').addEventListener('click', event => {
        const toggle = event.target.closest('[data-toggle]');
        const remove = event.target.closest('[data-delete]');
        if (toggle) {
            users = users.map(user => user.id === toggle.dataset.toggle ? { ...user, is_active:!user.is_active } : user);
            saveUsers(); render(); showToast('User access updated.');
        }
        if (remove) {
            const user = users.find(item => item.id === remove.dataset.delete);
            if (!user || !window.confirm(`Remove ${user.username} from this browser preview?`)) return;
            users = users.filter(item => item.id !== remove.dataset.delete);
            saveUsers(); render(); showToast('User removed from this browser preview.');
        }
    });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeModal(); });
    render();
</script>
</body>
</html>