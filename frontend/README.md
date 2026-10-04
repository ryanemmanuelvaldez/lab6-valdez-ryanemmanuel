# LavaLust Admin

React admin workspace for the existing LavaLust users schema and the products catalog. It reads and writes through `/api/*`; it does not keep account or inventory records in browser storage.

## Sign in and first admin

Admins and members use the same sign-in page at `/admin/`. After sign-in, admins are sent to the control room and users or moderators are sent to the member catalog at `/admin/account/`.

If the database has no administrator yet, add a unique secret to the ignored project `.env` file:

```dotenv
ADMIN_BOOTSTRAP_KEY=replace-with-a-long-random-secret
```

Restart the LavaLust server, open `/admin/`, and use that key in the first-admin setup form. Choose a password with at least 12 characters. The bootstrap endpoint is disabled as soon as an administrator account exists.

## Run locally

From PowerShell, run the PHP server in one terminal and the React watcher in another:

```powershell
Set-Location -LiteralPath 'C:\xampp\htdocs\Lab6\Lab#6'
php lava serve
```

```powershell
Set-Location -LiteralPath 'C:\xampp\htdocs\Lab6\Lab#6\frontend'
npm install
npm run dev
```

Open `http://localhost:3000/admin/`. The watcher rebuilds `public/admin/` after frontend edits; refresh the browser to load the latest bundle. Use `npm run build` for a one-time production build.

## Backend features

- Overview metrics are queried from `users` and `products`.
- Product CRUD uses `name`, unique `sku`, `category`, `description`, `price`, `stock`, and `status`.
- User management uses the existing `admin`, `moderator`, and `user` roles, active status, and password hashes.
- Migration status and pending migration execution are available to authenticated administrators.
- All management endpoints require an active admin session. Public bootstrap is allowed only when no admin exists and a bootstrap key is configured.
