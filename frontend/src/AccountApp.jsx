import { useEffect, useState } from 'react';
import { Check, Command, LoaderCircle, LogOut, Package, Search, ShieldCheck } from 'lucide-react';

async function accountApi(path, options = {}) {
  const response = await fetch(`../../index.php?route=api/account/${path}`, {
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
const initials = value => String(value || 'U').split(/[ ._-]+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase();

export default function AccountApp() {
  const [user, setUser] = useState(null);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  async function loadAccount() {
    const session = await accountApi('me');
    setUser(session.user);
    if (session.user) {
      const catalog = await accountApi('catalog');
      setProducts(catalog.products);
      setUser(catalog.user);
    }
  }

  useEffect(() => {
    let active = true;
    loadAccount().catch(() => { if (active) setUser(null); }).finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);

  useEffect(() => {
    if (!loading && !user) window.location.replace('../');
  }, [loading, user]);

  async function signOut() {
    await accountApi('logout', { method: 'POST' });
    setUser(null);
    setProducts([]);
    setSearch('');
  }

  const visibleProducts = products.filter(product => `${product.name} ${product.sku} ${product.category}`.toLowerCase().includes(search.trim().toLowerCase()));

  if (loading) return <div className="boot-screen"><LoaderCircle className="spin" size={24} /><span>Opening your account…</span></div>;

  if (!user) return <div className="boot-screen"><LoaderCircle className="spin" size={24} /><span>Redirecting to sign in…</span></div>;

  return <div className="member-shell">
    <header className="member-topbar"><a className="brand member-brand" href="#catalog"><span className="brand-symbol"><Command size={19} /></span><span>LavaLust <small>MEMBER PORTAL</small></span></a><div className="member-top-actions"><span className="member-user"><span className="avatar">{initials(user.username)}</span><span><b>{user.username}</b><small>{user.role}</small></span></span><button className="member-signout" onClick={signOut}><LogOut size={15} /><span>Sign out</span></button></div></header>
    <main className="member-content" id="catalog"><div className="member-welcome"><div><p className="eyebrow">CATALOG / MEMBER VIEW</p><h1>Browse the collection.</h1><p>Products currently available from the LavaLust catalog.</p></div><div className="member-count"><Package size={17} /><span>{products.length} active products</span></div></div>
      <div className="catalog-toolbar"><label className="search-box"><Search size={16} /><input value={search} onChange={event => setSearch(event.target.value)} placeholder="Search products, SKU, category…" /></label><span>Showing active listings</span></div>
      {visibleProducts.length ? <div className="member-product-grid">{visibleProducts.map(product => <article className="member-product" key={product.id}><div className="member-product-art"><span>{initials(product.name)}</span><span className="available"><i />{product.stock > 0 ? 'Available' : 'Out of stock'}</span></div><div className="member-product-body"><div className="member-product-meta"><span>{product.category}</span><span>{product.sku}</span></div><h2>{product.name}</h2><p>{product.description || 'Product details are available from the LavaLust team.'}</p><div className="member-product-footer"><b>{money(product.price)}</b><span>{product.stock > 0 ? `${product.stock} in stock` : 'Check back soon'}</span></div></div></article>)}</div> : <div className="member-empty"><Package size={22} /><b>{products.length ? 'No matching products' : 'No active listings yet'}</b><span>{products.length ? 'Try a different search.' : 'The catalog will show items when the administrator publishes products.'}</span></div>}
      <section className="member-profile"><span className="member-profile-icon"><ShieldCheck size={18} /></span><div><b>Account details</b><span>{user.email} · {user.role} account</span></div><span className="member-active"><Check size={13} /> Active</span></section>
      <footer className="member-footer"><span>LavaLust / member catalog</span><span>Signed in as {user.username}</span></footer>
    </main>
  </div>;
}
