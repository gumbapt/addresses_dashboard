# Cursor brief (Nuxt 3): Backup permissions → navigation & buttons

Paste this into the **frontend** Cursor agent. It complements `FRONTEND_NUXT_BACKUP_INTEGRATION_PROMPT.md` (API routes). Here the focus is **permission-driven UI**: what to show in the menu and which actions to expose, based on slugs returned at login—not only `is_super_admin`.

---

## 1. Why permissions matter (not only Sudo flag)

The backup API routes are protected by **`super.admin`** middleware today, so non–Sudo admins get **403** before the permission layer. Still, the product model assigns **explicit permission slugs** to roles (`backup-read`, etc.), and the **admin login** payload already includes **roles with nested permissions** (each permission has a `slug`). The frontend should:

1. **Derive a flat set of permission slugs** from `POST /api/admin/login` response: walk `roles[]` → `permissions[]` → collect each `slug` (dedupe). Ignore permissions where `is_active === false` if present.
2. **Drive navigation and buttons from those slugs** so that if future policies allow backup routes for non-Sudo roles that have the right slugs, the UI will match without a redeploy.
3. **Optionally combine** with `admin.is_super_admin` (or equivalent in your DTO) as a shortcut: Sudo users effectively have all backup slugs after a fresh seed, but the **single source of truth for “can see backup area” in code** should still be **`can('backup-read')`-style helpers** built from slugs.

---

## 2. Backup permission slugs (catalogue)

| Slug | Typical use in UI |
|------|-------------------|
| `backup-read` | Enter **Backups** section; list backups; view detail; **download** export files. |
| `backup-create` | Show **“Run backup”** / create backup action (global or domain scope). |
| `backup-restore` | Show **“Restore”** control (even if API returns 501 until implemented—hide if no slug). |
| `backup-config-manage` | Show **settings** for periodic backups (`GET`/`PUT` backup-config); link in nav or settings tab. |
| `backup-audit-read` | Show **audit log** page or tab (who requested backup, config changes, etc.). |

**Minimum to show any backup entry in the nav:** treat `backup-read` as the gate for the parent menu item (or the whole “Backups” group).

---

## 3. Mapping: slug → navigation & buttons

Use this as a checklist when implementing composables / directives / `v-if` equivalents:

- **Side nav / top nav “Backups” (or under System / Admin)**  
  - Visible if: has `backup-read` **OR** (optional) `is_super_admin === true` if your product always shows sysadmin menus that way.

- **Sub-routes or tabs** (adjust names to your router)  
  - **History / list:** `backup-read`.  
  - **Run backup (CTA):** `backup-create`.  
  - **Configuration (periodic):** `backup-config-manage`.  
  - **Audit log:** `backup-audit-read`.  
  - **Restore (per backup):** `backup-restore`.

- **Per-row actions on backup list**  
  - **View detail:** `backup-read`.  
  - **Download file:** `backup-read` (same as API `backup-read` on download endpoint).  
  - **Restore:** `backup-restore`.

- **Empty permission set**  
  - If the user has no `backup-*` slugs, **do not** render backup navigation or FAB; avoids 403 loops if middleware ever aligns with permissions only.

---

## 4. Suggested frontend patterns (no HTML)

1. **`usePermissions()` (or extend existing auth store)**  
   - On login, store `permissionSlugs: Set<string>` or `string[]`.  
   - Expose `hasPermission('backup-read')` and `hasAnyPermission(prefix: 'backup')` if useful.

2. **Middleware / route meta**  
   - Meta e.g. `requiredPermission: 'backup-read'` on backup layout routes; redirect or show “no access” if missing.

3. **Nav config as data**  
   - Define menu items with `permission: 'backup-read'` (or array of slugs); filter menu by `hasPermission` so new backup features stay declarative.

4. **Buttons**  
   - Prefer **hidden** when lacking permission (cleaner than disabled) unless UX requires “visible but disabled” with tooltip (“No permission”).

5. **Loading / hydration**  
   - Until login payload is applied, avoid flashing backup links: wait for auth + permission flattening before rendering restricted nav.

6. **`is_super_admin` shortcut**  
   - If your app already treats Sudo as “all permissions,” you may map `is_super_admin` to `hasPermission('*')` **or** explicitly set all `backup-*` to true for that user—**but** keep the **same helper** (`hasPermission('backup-create')`) in templates so logic stays one-dimensional.

---

## 5. Login payload reminder

`POST /api/admin/login` returns among other fields:

- `admin` — includes `is_super_admin` (check your DTO field names).  
- `roles` — array of `{ id, name, description, permissions: [{ id, name, slug, description, is_active, resource, action, route }] }`.

Flatten `slug` from nested `permissions` for the checks above.

---

## 6. One-line instruction for Cursor

*“Implement backup navigation and all backup-related buttons using permission slugs from the admin login `roles[].permissions[].slug` (especially `backup-read`, `backup-create`, `backup-restore`, `backup-config-manage`, `backup-audit-read`); hide routes and actions when the slug is missing; optionally treat `is_super_admin` as full access but still route checks through a single `hasPermission` helper.”*
