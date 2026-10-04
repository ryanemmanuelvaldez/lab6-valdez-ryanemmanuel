import { useEffect, useState } from 'react';
import {
  Activity, Archive, ArrowDownToLine, ArrowUpRight, Boxes, CalendarDays, Check, ChevronDown,
  CircleAlert, CircleHelp, Command, Database, Ellipsis, Eye, FileClock, LayoutDashboard,
  LoaderCircle, LogOut, Mail, Package, Pencil, Plus, Search, Shield, ShieldCheck,
  ShoppingBag, Trash2, UserRound, Users, X,
} from 'lucide-react';

const navigation = [
  { id: 'overview', label: 'Overview', icon: LayoutDashboard },
  { id: 'products', label: 'Products', icon: Package },
  { id: 'users', label: 'Users', icon: Users },
  { id: 'migrations', label: 'Migrations', icon: Database },
];

async function api(path, options = {}) {
  const response = await fetch(`../index.php?route=api/${path}`, {
    credentials: 'include',
    cache: 'no-store',
    ...options,
    headers: {
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...options.headers,
    },
  });
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(payload.error || `Request failed (${response.status})`);
  return payload;
}

const money = value => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0));
const dateLabel = value => value ? new Date(value.replace(' ', 'T')).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
const initials = value => String(value || 'A').split(/[ ._-]+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase();

function App() {
  const [admin, setAdmin] = useState(null);
  const [bootstrap, setBootstrap] = useState(null);
  const [page, setCurrentPage] = useState(() => {
    const requestedPage = window.location.hash.slice(1);
    return navigation.some(item => item.id === requestedPage) ? requestedPage : 'overview';
  });
  const [data, setData] = useState({ dashboard: null, products: [], users: [], migrations: [] });
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState(null);
  const [modal, setModal] = useState(null);
  const [search, setSearch] = useState('');
  const [productFilter, setProductFilter] = useState('all');
  const [userFilter, setUserFilter] = useState('all');
  const [authError, setAuthError] = useState('');

  function setPage(nextPage) {
    if (!navigation.some(item => item.id === nextPage)) return;
    setCurrentPage(nextPage);
    window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${nextPage}`);
  }

  const tell = (message, kind = 'success') => {
    setNotice({ message, kind });
    window.setTimeout(() => setNotice(null), 3400);
  };

  async function loadWorkspace() {
    const [dashboard, products, users, migrations] = await Promise.all([
      api('dashboard'), api('products'), api('users'), api('migrations'),
    ]);
    setData({ dashboard, products: products.products, users: users.users, migrations: migrations.migrations });
  }

  useEffect(() => {
    let active = true;
    Promise.all([api('auth/me'), api('auth/bootstrap')])
      .then(async ([auth, setup]) => {
        if (!active) return;
        setBootstrap(setup);
        if (auth.user?.role === 'admin') {
          setAdmin(auth.user);
          await loadWorkspace();
        } else if (auth.user) {
          window.location.replace('./account/');
        }
      })
      .catch(error => { if (active) setAuthError(error.message); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);

  async function refresh() {
    setBusy(true);
    try { await loadWorkspace(); }
    catch (error) { tell(error.message, 'error'); }
    finally { setBusy(false); }
  }

  async function authenticate(event) {
    event.preventDefault();
    setBusy(true);
    setAuthError('');
    const form = new FormData(event.currentTarget);
    const setupMode = bootstrap?.available;
    try {
      const response = await api(setupMode ? 'auth/bootstrap' : 'auth/login', {
        method: 'POST',
        body: JSON.stringify(setupMode ? {
          bootstrap_key: form.get('bootstrap_key'),
          username: form.get('username'),
          email: form.get('email'),
          password: form.get('password'),
        } : { identity: form.get('identity'), password: form.get('password') }),
      });
      if (response.user.role !== 'admin') {
        window.location.assign('./account/');
        return;
      }
      setAdmin(response.user);
      setBootstrap({ ...bootstrap, available: false, admin_exists: true });
      await loadWorkspace();
    } catch (error) { setAuthError(error.message); }
    finally { setBusy(false); }
  }

  async function signOut() {
    try { await api('auth/logout', { method: 'POST' }); }
    finally { setAdmin(null); setData({ dashboard: null, products: [], users: [], migrations: [] }); setPage('overview'); }
  }

  async function submitProduct(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const product = Object.fromEntries(form.entries());
    product.price = Number(product.price);
    product.stock = Number(product.stock);
    setBusy(true);
    try {
      const editing = modal?.type === 'product-edit';
      await api(editing ? `products/${modal.item.id}` : 'products', {
        method: editing ? 'PUT' : 'POST', body: JSON.stringify(product),
      });
      setModal(null);
      await loadWorkspace();
      tell(editing ? 'Product updated.' : 'Product added to inventory.');
    } catch (error) { tell(error.message, 'error'); }
    finally { setBusy(false); }
  }

  async function removeProduct(product) {
    if (!window.confirm(`Delete ${product.name}? This cannot be undone.`)) return;
    try { await api(`products/${product.id}`, { method: 'DELETE' }); await loadWorkspace(); tell('Product deleted.'); }
    catch (error) { tell(error.message, 'error'); }
  }

  async function submitUser(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const user = Object.fromEntries(form.entries());
    setBusy(true);
    try {
      await api('users', { method: 'POST', body: JSON.stringify(user) });
      setModal(null);
      await loadWorkspace();
      tell('User account created.');
    } catch (error) { tell(error.message, 'error'); }
    finally { setBusy(false); }
  }

  async function updateUser(user, patch) {
    try {
      await api(`users/${user.id}`, { method: 'PATCH', body: JSON.stringify(patch) });
      await loadWorkspace();
      tell('User updated.');
    } catch (error) { tell(error.message, 'error'); }
  }

  async function removeUser(user) {
    if (!window.confirm(`Remove ${user.username}'s account?`)) return;
    try { await api(`users/${user.id}`, { method: 'DELETE' }); await loadWorkspace(); tell('User removed.'); }
    catch (error) { tell(error.message, 'error'); }
  }

  async function runMigrations() {
    if (!window.confirm('Run all pending migrations against the connected database?')) return;
    setBusy(true);
    try {
      const result = await api('migrations/run', { method: 'POST', body: '{}' });
      tell(result.output || 'Migration check completed.');
      await loadWorkspace();
    } catch (error) { tell(error.message, 'error'); }
    finally { setBusy(false); }
  }

  if (loading) return <div className="boot-screen"><LoaderCircle className="spin" size={24} /><span>Connecting to LavaLust…</span></div>;
  if (!admin) return <AuthScreen bootstrap={bootstrap} error={authError} busy={busy} onSubmit={authenticate} />;

  const title = navigation.find(item => item.id === page)?.label || 'Overview';
  const query = search.trim().toLowerCase();
  const products = data.products.filter(product => {
    const matches = `${product.name} ${product.sku} ${product.category}`.toLowerCase().includes(query);
    return matches && (productFilter === 'all' || product.status === productFilter);
  });
  const users = data.users.filter(user => {
    const matches = `${user.username} ${user.email}`.toLowerCase().includes(query);
    return matches && (userFilter === 'all' || user.role === userFilter);
  });

  return (
    <div className="app-frame">
      <aside className="sidebar">
        <a className="brand" href="#overview" onClick={() => setPage('overview')}>
          <span className="brand-symbol"><Command size={19} /></span>
          <span>LavaLust <small>CONTROL ROOM</small></span>
        </a>
        <div className="nav-caption">Workspace</div>
        <nav className="side-nav" aria-label="Admin navigation">
          {navigation.map(item => {
            const Icon = item.icon;
            return <button key={item.id} className={page === item.id ? 'selected' : ''} aria-label={item.label} title={item.label} aria-current={page === item.id ? 'page' : undefined} onClick={() => { setPage(item.id); setSearch(''); }}><Icon size={17} /><span>{item.label}</span>{item.id === 'products' && <small>{data.products.length}</small>}</button>;
          })}
        </nav>
        <div className="sidebar-bottom">
          <div className="server-card"><span className="server-pulse" /><span><b>Database connected</b><small>Live records · protected API</small></span><ArrowUpRight size={15} /></div>
          <div className="profile-card"><div className="avatar">{initials(admin.username)}</div><span className="profile-copy"><b>{admin.username}</b><small>Administrator</small></span><button className="icon-button sidebar-logout" title="Sign out" aria-label="Sign out" onClick={signOut}><LogOut size={16} /></button></div>
        </div>
      </aside>

      <main className="main-area">
        <header className="topbar"><div className="breadcrumb"><span>Admin</span><b>/</b><strong>{title}</strong></div><div className="topbar-tools"><span className="live-label"><i /> Connected</span><button className="icon-button" aria-label="Refresh data" title="Refresh data" onClick={refresh}><ArrowDownToLine size={17} className={busy ? 'spin' : ''} /></button><div className="mini-avatar">{initials(admin.username)}</div></div></header>
        <div className="page-content">
          {page === 'overview' && <Overview dashboard={data.dashboard} products={data.products} users={data.users} onNavigate={setPage} onAddProduct={() => setModal({ type: 'product-new' })} />}
          {page === 'products' && <ProductsPage products={products} allCount={data.products.length} search={search} setSearch={setSearch} filter={productFilter} setFilter={setProductFilter} onAdd={() => setModal({ type: 'product-new' })} onEdit={item => setModal({ type: 'product-edit', item })} onDelete={removeProduct} />}
          {page === 'users' && <UsersPage users={users} allCount={data.users.length} search={search} setSearch={setSearch} filter={userFilter} setFilter={setUserFilter} onAdd={() => setModal({ type: 'user-new' })} onView={user => setModal({ type: 'user-view', item: user })} onUpdate={updateUser} onDelete={removeUser} currentId={admin.id} />}
          {page === 'migrations' && <MigrationsPage migrations={data.migrations} onRun={runMigrations} busy={busy} />}
        </div>
      </main>

      {modal?.type === 'user-view' && <Modal onClose={() => setModal(null)} title={`Account details · ${modal.item.username}`}><UserDetails user={modal.item} /></Modal>}
      {modal && modal.type !== 'user-view' && <Modal onClose={() => setModal(null)} title={modal.type.startsWith('product') ? (modal.type === 'product-edit' ? 'Edit product' : 'Add product') : 'Create user account'}>
        {modal.type.startsWith('product') ? <ProductForm item={modal.item} busy={busy} onSubmit={submitProduct} /> : <UserForm busy={busy} onSubmit={submitUser} />}
      </Modal>}
      {notice && <div className={`toast ${notice.kind}`} role="status"><span>{notice.kind === 'error' ? <CircleAlert size={17} /> : <Check size={17} />}</span>{notice.message}</div>}
    </div>
  );
}

function AuthScreen({ bootstrap, error, busy, onSubmit }) {
  const setup = Boolean(bootstrap?.available);
  const needsKey = bootstrap && !bootstrap.admin_exists && !bootstrap.key_configured;
  return <main className="auth-shell">
    <div className="auth-aside"><a className="brand auth-brand" href="#"><span className="brand-symbol"><Command size={19} /></span><span>LavaLust <small>CONTROL ROOM</small></span></a><div className="auth-art"><div className="art-grid" /><div className="art-stamp">LL<br /><small>ADMIN</small></div><p>Inventory in sync.<br /><em>People in control.</em></p><span>One workspace for your catalog and team.</span></div><div className="auth-aside-foot">PRODUCT OPERATIONS / 2026</div></div>
    <section className="auth-panel"><div className="auth-panel-inner"><div className="auth-icon">{setup ? <ShieldCheck size={22} /> : <Shield size={22} />}</div><p className="eyebrow">{setup ? 'FIRST-TIME SETUP' : 'SECURE WORKSPACE'}</p><h1>{setup ? 'Create your admin.' : 'Welcome back.'}</h1><p className="auth-description">{setup ? 'Set up the first administrator account to unlock your product and user workspace.' : 'Sign in with your username or email. Your role opens the right workspace.'}</p>
      {needsKey ? <div className="setup-callout"><CircleHelp size={19} /><div><b>Admin bootstrap is not configured</b><p>Add a unique <code>ADMIN_BOOTSTRAP_KEY</code> to the project `.env`, then restart the PHP server. The first admin can then be created here.</p></div></div> : <form className="auth-form" onSubmit={onSubmit}>
        {setup ? <><Field label="Bootstrap key" name="bootstrap_key" placeholder="Key configured in .env" required /><Field label="Username" name="username" placeholder="admin.name" minLength="3" required /><Field label="Email" name="email" type="email" placeholder="admin@example.com" required /></> : <Field label="Username or email" name="identity" autoComplete="username" placeholder="you@example.com" required />}
        <Field label="Password" name="password" type="password" autoComplete={setup ? 'new-password' : 'current-password'} minLength={setup ? '12' : undefined} placeholder={setup ? 'At least 12 characters' : 'Your password'} required />
        {error && <div className="form-error"><CircleAlert size={16} />{error}</div>}
        <button className="button primary auth-submit" disabled={busy}>{busy ? <LoaderCircle className="spin" size={17} /> : setup ? <ShieldCheck size={17} /> : <ArrowUpRight size={17} />}{setup ? 'Create administrator' : 'Sign in to workspace'}</button>
      </form>}
      <div className="auth-foot"><span><i /> Encrypted session</span><span>PHP · LavaLust API</span></div></div></section>
  </main>;
}

function Field({ label, ...props }) {
  return <label className="field"><span>{label}</span><input {...props} /></label>;
}

function Overview({ dashboard, products, users, onNavigate, onAddProduct }) {
  const lowStock = products.filter(product => product.status !== 'archived' && product.stock <= 5).slice(0, 5);
  const recentProducts = [...products].slice(0, 5);
  const activeUsers = users.filter(user => user.is_active).length;
  return <>
    <section className="welcome-row"><div><p className="eyebrow">OPERATIONS / AT A GLANCE</p><h1>Your workspace, <em>in motion.</em></h1><p>Real-time inventory and account activity from your LavaLust database.</p></div><button className="button primary" onClick={onAddProduct}><Plus size={17} />Add product</button></section>
    <div className="stat-grid">
      <Stat icon={ShoppingBag} label="Products" value={dashboard?.products ?? '—'} meta={`${dashboard?.active_products ?? 0} active listings`} tone="green" />
      <Stat icon={Users} label="Team accounts" value={dashboard?.users ?? '—'} meta={`${activeUsers} currently active`} tone="blue" />
      <Stat icon={CircleAlert} label="Low stock" value={dashboard?.low_stock ?? '—'} meta="Five units or fewer" tone="coral" />
      <Stat icon={Boxes} label="Inventory value" value={money(dashboard?.inventory_value)} meta="Active and draft stock" tone="gold" />
    </div>
    <div className="overview-grid">
      <section className="surface overview-inventory"><div className="section-head"><div><p className="eyebrow">CATALOG / RECENT</p><h2>Recently added</h2></div><button className="text-action" onClick={() => onNavigate('products')}>All products <ArrowUpRight size={15} /></button></div>
        {recentProducts.length ? <div className="recent-list">{recentProducts.map(product => <div className="recent-row" key={product.id}><div className="product-initial">{initials(product.name)}</div><div className="recent-copy"><b>{product.name}</b><small>{product.sku} <span>·</span> {product.category}</small></div><div className="recent-stock"><b>{money(product.price)}</b><small>{product.stock} in stock</small></div><StatusPill value={product.status} /></div>)}</div> : <EmptyState icon={Package} title="No products yet" detail="Add your first product to start tracking inventory." action="Add a product" onAction={onAddProduct} />}
      </section>
      <section className="surface attention-panel"><div className="section-head"><div><p className="eyebrow">INVENTORY / ATTENTION</p><h2>Stock watch</h2></div><span className="count-pill">{lowStock.length} items</span></div>
        {lowStock.length ? <div className="stock-list">{lowStock.map(product => <div className="stock-row" key={product.id}><div><b>{product.name}</b><small>{product.sku}</small></div><span className={product.stock === 0 ? 'stock-number empty-stock' : 'stock-number'}>{product.stock}<small>left</small></span></div>)}</div> : <div className="quiet-state"><Check size={18} /><span>Everything is comfortably stocked.</span></div>}
        <button className="wide-link" onClick={() => onNavigate('users')}><span><Users size={16} />{users.length} team accounts <small>{activeUsers} active</small></span><ArrowUpRight size={15} /></button>
      </section>
    </div>
    <section className="platform-strip"><div className="platform-mark"><Activity size={17} /></div><div><b>Backend services are online</b><small>Authenticated admin session · Products and users stored in MySQL</small></div><span className="platform-chip"><Database size={13} /> LAVALUST</span></section>
  </>;
}

function Stat({ icon: Icon, label, value, meta, tone }) {
  return <article className={`stat-card ${tone}`}><div className="stat-top"><span>{label}</span><span className="stat-icon"><Icon size={17} /></span></div><strong>{value}</strong><small>{meta}</small></article>;
}

function ProductsPage({ products, allCount, search, setSearch, filter, setFilter, onAdd, onEdit, onDelete }) {
  return <>
    <PageHeading eyebrow="CATALOG / INVENTORY" title="Products" description="Manage catalog details, availability, and stock levels." action={<button className="button primary" onClick={onAdd}><Plus size={17} />Add product</button>} />
    <section className="surface table-surface"><div className="table-toolbar"><label className="search-box"><Search size={16} /><input value={search} onChange={event => setSearch(event.target.value)} placeholder="Search product, SKU, category…" /></label><div className="toolbar-right"><select value={filter} onChange={event => setFilter(event.target.value)} aria-label="Filter product status"><option value="all">All status</option><option value="active">Active</option><option value="draft">Draft</option><option value="archived">Archived</option></select><span className="result-label">{products.length} / {allCount}</span></div></div>
      {products.length ? <div className="table-scroll"><table className="data-table"><thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th aria-label="Actions" /></tr></thead><tbody>{products.map(product => <tr key={product.id}><td><div className="table-product"><span className="product-initial">{initials(product.name)}</span><span><b>{product.name}</b><small>{product.description || 'No description'}</small></span></div></td><td className="mono">{product.sku}</td><td>{product.category}</td><td className="price-cell">{money(product.price)}</td><td><span className={product.stock <= 5 ? 'stock-inline low' : 'stock-inline'}>{product.stock}</span></td><td><StatusPill value={product.status} /></td><td><div className="row-actions"><button className="icon-button" title="Edit product" aria-label={`Edit ${product.name}`} onClick={() => onEdit(product)}><Pencil size={15} /></button><button className="icon-button remove" title="Delete product" aria-label={`Delete ${product.name}`} onClick={() => onDelete(product)}><Trash2 size={15} /></button></div></td></tr>)}</tbody></table></div> : <EmptyState icon={Package} title="No matching products" detail={allCount ? 'Try a different search or status filter.' : 'Your catalog is empty. Add a product to begin.'} action={allCount ? undefined : 'Add product'} onAction={onAdd} />}
      <div className="table-foot"><span><i /> Live from products table</span><span>{products.length} records</span></div>
    </section>
  </>;
}

function UsersPage({ users, allCount, search, setSearch, filter, setFilter, onAdd, onView, onUpdate, onDelete, currentId }) {
  return <>
    <PageHeading eyebrow="ACCESS / TEAM" title="Users" description="Manage account roles and active access. Passwords are never displayed." action={<button className="button primary" onClick={onAdd}><Plus size={17} />Add user</button>} />
    <section className="surface table-surface"><div className="table-toolbar"><label className="search-box"><Search size={16} /><input value={search} onChange={event => setSearch(event.target.value)} placeholder="Search username or email…" /></label><div className="toolbar-right"><select value={filter} onChange={event => setFilter(event.target.value)} aria-label="Filter user role"><option value="all">All roles</option><option value="admin">Admin</option><option value="moderator">Moderator</option><option value="user">User</option></select><span className="result-label">{users.length} / {allCount}</span></div></div>
      {users.length ? <div className="table-scroll"><table className="data-table user-table"><thead><tr><th>Account</th><th>Role</th><th>Status</th><th>Created</th><th aria-label="Actions" /></tr></thead><tbody>{users.map(user => <tr key={user.id}><td><button className="user-identity account-open" type="button" onClick={() => onView(user)} aria-label={`Open ${user.username} account details`}><span className="avatar">{initials(user.username)}</span><span><b>{user.username}</b><small>{user.email}</small></span><Eye size={14} /></button></td><td><select className="role-select" aria-label={`Role for ${user.username}`} value={user.role} disabled={Number(user.id) === Number(currentId)} onChange={event => onUpdate(user, { role: event.target.value })}><option value="admin">Admin</option><option value="moderator">Moderator</option><option value="user">User</option></select></td><td><button className={`access-toggle ${user.is_active ? 'enabled' : ''}`} aria-label={`${user.is_active ? 'Deactivate' : 'Activate'} ${user.username}`} onClick={() => onUpdate(user, { is_active: !user.is_active })}><i />{user.is_active ? 'Active' : 'Inactive'}</button></td><td>{dateLabel(user.created_at)}</td><td><div className="row-actions">{Number(user.id) !== Number(currentId) && <button className="icon-button remove" title="Remove user" aria-label={`Remove ${user.username}`} onClick={() => onDelete(user)}><Trash2 size={15} /></button>}</div></td></tr>)}</tbody></table></div> : <EmptyState icon={Users} title="No matching accounts" detail={allCount ? 'Try another search or role.' : 'Create a user account to invite your team.'} action={allCount ? undefined : 'Add user'} onAction={onAdd} />}
      <div className="table-foot"><span><ShieldCheck size={14} /> Admin-only user controls</span><span>{users.length} records</span></div>
    </section>
    <div className="role-note"><Shield size={17} /><div><b>Role options match your database schema</b><span>Admin · Moderator · User. The last active administrator cannot be removed or deactivated.</span></div></div>
  </>;
}

function MigrationsPage({ migrations, onRun, busy }) {
  const applied = migrations.filter(item => item.applied).length;
  const pending = migrations.length - applied;
  return <>
    <PageHeading eyebrow="DATABASE / SCHEMA HISTORY" title="Migrations" description="Review schema versions and apply pending changes through the protected backend." action={<button className="button primary" onClick={onRun} disabled={busy}><Database size={16} />Run pending</button>} />
    <div className="migration-summary"><div className="migration-total"><span>Applied versions</span><b>{applied}<small> / {migrations.length}</small></b><div className="progress-track"><i style={{ width: `${migrations.length ? applied / migrations.length * 100 : 0}%` }} /></div></div><div className={`pending-note ${pending ? 'has-pending' : ''}`}><span>{pending ? <CircleAlert size={18} /> : <Check size={18} />}</span><div><b>{pending ? `${pending} pending migration${pending > 1 ? 's' : ''}` : 'Schema is up to date'}</b><small>{pending ? 'Apply only after reviewing the migration changes.' : 'No database updates are waiting.'}</small></div></div></div>
    <section className="surface table-surface"><div className="section-head migrations-head"><div><p className="eyebrow">VERSION CONTROL</p><h2>Migration history</h2></div><span className="source-tag"><FileClock size={14} /> app/migrations</span></div>{migrations.length ? <div className="migration-list">{migrations.map(item => <div className="migration-row" key={item.version}><div className={`migration-state ${item.applied ? 'done' : ''}`}>{item.applied ? <Check size={15} /> : <Ellipsis size={15} />}</div><span className="migration-version">{String(item.version).padStart(3, '0')}</span><b>{item.file}</b><span className={`migration-label ${item.applied ? 'done' : ''}`}>{item.applied ? 'APPLIED' : 'PENDING'}</span></div>)}</div> : <EmptyState icon={Archive} title="No migration files found" detail="Migrations from app/migrations appear here." />}</section>
  </>;
}

function PageHeading({ eyebrow, title, description, action }) {
  return <div className="page-heading"><div><p className="eyebrow">{eyebrow}</p><h1>{title}</h1><p>{description}</p></div>{action}</div>;
}

function StatusPill({ value }) {
  return <span className={`status-pill ${value}`}><i />{value}</span>;
}

function EmptyState({ icon: Icon, title, detail, action, onAction }) {
  return <div className="empty-state"><span className="empty-icon"><Icon size={21} /></span><b>{title}</b><p>{detail}</p>{action && <button className="button" onClick={onAction}><Plus size={15} />{action}</button>}</div>;
}

function Modal({ title, onClose, children }) {
  useEffect(() => {
    const keyHandler = event => { if (event.key === 'Escape') onClose(); };
    window.addEventListener('keydown', keyHandler);
    return () => window.removeEventListener('keydown', keyHandler);
  }, [onClose]);
  return <div className="modal-layer" onMouseDown={event => { if (event.target === event.currentTarget) onClose(); }}><section className="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title"><header><h2 id="modal-title">{title}</h2><button className="icon-button" onClick={onClose} aria-label="Close dialog"><X size={17} /></button></header>{children}</section></div>;
}

function ProductForm({ item, busy, onSubmit }) {
  return <form className="record-form" onSubmit={onSubmit}>
    <div className="form-grid"><Field label="Product name" name="product_name" maxLength="100" defaultValue={item?.product_name || item?.name || ''} placeholder="e.g. Ceramic pour-over" required /><Field label="SKU (optional)" name="sku" maxLength="80" defaultValue={item?.sku || ''} placeholder="Generated if omitted" /><Field label="Category" name="category" maxLength="100" defaultValue={item?.category || 'General'} placeholder="Homeware" required /><Field label="Price (PHP)" name="price" type="number" min="0" max="99999999.99" step="0.01" defaultValue={item?.price ?? ''} placeholder="0.00" required /><Field label="Quantity" name="quantity" type="number" min="0" step="1" defaultValue={item?.quantity ?? item?.stock ?? 0} required /><label className="field"><span>Status</span><select name="status" defaultValue={item?.status || 'active'}><option value="active">Active</option><option value="draft">Draft</option><option value="archived">Archived</option></select></label><label className="field full-field"><span>Description <small>Optional</small></span><textarea name="description" rows="3" maxLength="3000" defaultValue={item?.description || ''} placeholder="Short product details" /></label></div>
    <div className="form-footer"><span>Product name, price, and quantity are saved to MySQL.</span><button className="button primary" disabled={busy}>{busy ? <LoaderCircle className="spin" size={16} /> : <Check size={16} />}{item ? 'Save changes' : 'Create product'}</button></div>
  </form>;
}

function UserForm({ busy, onSubmit }) {
  return <form className="record-form" onSubmit={onSubmit}><div className="form-grid"><Field label="Username" name="username" minLength="3" maxLength="100" placeholder="alex.rivera" required /><Field label="Email" name="email" type="email" maxLength="255" placeholder="alex@example.com" required /><label className="field"><span>Role</span><select name="role" defaultValue="user"><option value="user">User</option><option value="moderator">Moderator</option><option value="admin">Admin</option></select></label><Field label="Temporary password" name="password" type="password" minLength="12" autoComplete="new-password" placeholder="At least 12 characters" required /></div><p className="form-hint"><ShieldCheck size={15} /> Passwords are stored as secure hashes and never returned by the API.</p><div className="form-footer"><span>Account starts active.</span><button className="button primary" disabled={busy}>{busy ? <LoaderCircle className="spin" size={16} /> : <Plus size={16} />}Create user</button></div></form>;
}

function UserDetails({ user }) {
  return <div className="user-details">
    <div className="user-details-profile"><span className="avatar">{initials(user.username)}</span><div><b>{user.username}</b><span>{user.email}</span></div><StatusPill value={user.is_active ? 'active' : 'inactive'} /></div>
    <div className="user-details-grid"><div><span><ShieldCheck size={14} />Role</span><b>{user.role}</b></div><div><span><Check size={14} />Access</span><b>{user.is_active ? 'Active' : 'Inactive'}</b></div><div><span><CalendarDays size={14} />Created</span><b>{dateLabel(user.created_at)}</b></div><div><span><Mail size={14} />Email</span><b>{user.email}</b></div></div>
    <div className="user-portal-note"><UserRound size={16} /><span>Users sign in separately at <a href="./account/" target="_blank" rel="noreferrer">/admin/account/</a> with their own username and password.</span></div>
  </div>;
}

export default App;
