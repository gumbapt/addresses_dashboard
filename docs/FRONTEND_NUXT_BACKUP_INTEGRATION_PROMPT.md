# Cursor agent brief: Nuxt 3 — Backup & restore admin UI (API integration)

Use this document in the **Nuxt 3 frontend** repository. It describes the Laravel API added for backups: routes, payloads, permissions, and product behaviour so you can implement composables, pages, and UX without guessing.

**See also:** [`FRONTEND_NUXT_BACKUP_PERMISSIONS_NAV_PROMPT.md`](./FRONTEND_NUXT_BACKUP_PERMISSIONS_NAV_PROMPT.md) — permission slugs for **navigation and button visibility** (login payload, `hasPermission`, route meta).

---

## 1. Context

- All backup endpoints live under the **admin API**, same authentication as the rest of the dashboard: **Bearer token** (Laravel Sanctum) for an **Admin** user.
- On the server, these routes are wrapped in middleware **`super.admin`**: only users with `is_super_admin === true` (Sudo Admin) receive **200** on success. Others get **403** with JSON such as `{ "success": false, "message": "Access denied. Only Super Admins can perform this action.", "required_permission": "super_admin" }`.
- In addition, the API checks **permission slugs** (`backup-read`, `backup-create`, etc.). Today those permissions are assigned effectively to the Sudo tier; non-sudo admins may get **403** with `{ "success": false, "error": "..." }` from the authorization layer if they ever gain route access without the slug.
- **Base path prefix:** assume the same base URL as existing admin calls, e.g. `{API_URL}/api/admin/...` (confirm with project env, e.g. `NUXT_PUBLIC_API_URL`).

---

## 2. Route reference

| Method | Path | Permission slug (conceptual) | Success | Notes |
|--------|------|------------------------------|---------|--------|
| `GET` | `/backups` | `backup-read` | 200 | Paginated list; optional filters. |
| `POST` | `/backups` | `backup-create` | 201 | Runs backup **synchronously**; can take time if many reports. |
| `GET` | `/backups/{id}` | `backup-read` | 200 | Detail + `files` array. |
| `GET` | `/backups/{backupId}/files/{fileId}` | `backup-read` | 200 | **File download** (attachment), not JSON. |
| `POST` | `/backups/{id}/restore` | `backup-restore` | **501** today | Not implemented; backend returns JSON explaining restore is not available yet. |
| `GET` | `/backup-config` | `backup-config-manage` | 200 | Singleton config. |
| `PUT` | `/backup-config` | `backup-config-manage` | 200 | Update periodic settings. |
| `GET` | `/backup-audit` | `backup-audit-read` | 200 | Paginated audit log. |

Full URL examples: `GET ${apiBase}/api/admin/backups`, etc.

---

## 3. Request / response contracts

### 3.1 `GET /api/admin/backups`

**Query:** `page` (default 1), `per_page` (default 15, max 100), optional `scope` (`global` \| `domain`), optional `status` (`pending` \| `running` \| `completed` \| `failed`), optional `domain_id` (integer).

**200 body:**

```json
{
  "success": true,
  "data": [ /* Backup rows, see §3.4, without "files" */ ],
  "pagination": {
    "total": 0,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1,
    "from": null,
    "to": null
  }
}
```

### 3.2 `POST /api/admin/backups`

**Body (JSON):**

- `scope` (required): `"global"` | `"domain"`
- `domain_id` (optional): required in practice when `scope === "domain"`; must exist in `domains` table or API returns **422**
- `notes` (optional): string

**201 body:** `success`, `message`, `data` = backup **with** `files` array (export completed in same request).

**422:** `success: false`, `message`, `errors` (Laravel validation shape).

**500:** backup run failed after persistence; `data` may still exist in DB with `status: failed` — refresh list to reconcile.

### 3.3 `GET /api/admin/backups/{id}`

**200:** `success`, `data` = backup **with** `files`.

**404:** backup not found.

### 3.4 Backup object shape (JSON `data`)

- `id` (number)
- `scope`: `"global"` | `"domain"`
- `domain_id`: number | null
- `started_at`, `completed_at`, `created_at`, `updated_at`: strings `"Y-m-d H:i:s"` or null
- `trigger_type`: `"manual"` | `"automatic"`
- `requested_by_admin_id`: number | null (null for automatic/cron)
- `reports_count_at_backup`: number (snapshot count at run time)
- `status`: `"pending"` \| `"running"` \| `"completed"` \| `"failed"`
- `error_message`: string | null
- `notes`: string | null
- `size_bytes`: number | null
- `files` (only when detail or create response): array of `{ id, disk, path, original_filename, mime_type, size_bytes, created_at }`

Use `scope` + `domain_id` + `trigger_type` + `status` for badges, filters, and explanations in the UI.

### 3.5 `GET /api/admin/backups/{backupId}/files/{fileId}`

- Response is a **binary download** (exported JSON file, typically `reports_export.json`).
- Send `Authorization: Bearer <token>`; handle **`responseType: 'blob'`** (or equivalent in `$fetch` / `ofetch`).
- Suggest deriving filename from `Content-Disposition` or fallback to `original_filename` from the detail payload.

### 3.6 `POST /api/admin/backups/{id}/restore`

- **501** with `{ "success": false, "message": "..." }` — inform the user that restore is not implemented; optionally hide the button or show as disabled with tooltip until the API is ready.

### 3.7 `GET /api/admin/backup-config`

**200 `data`:**

- `id`, `periodic_enabled` (boolean), `interval_hours` (int)
- `last_run_at`, `next_run_at`: ISO 8601 strings or null
- `updated_by_admin_id`: number | null

### 3.8 `PUT /api/admin/backup-config`

**Body:** at least one of `periodic_enabled` (boolean), `interval_hours` (integer 1–8760).

**Behaviour (for UI copy):** enabling periodic sets `next_run_at` to approximately “now + interval”; disabling clears `next_run_at`. Actual execution depends on server **scheduler** (`backup:run-periodic` hourly).

**200:** same `data` shape as GET.

### 3.9 `GET /api/admin/backup-audit`

**Query:** `page`, `per_page` (same limits as backups).

**200 `data`:** array of rows:

- `id`, `admin_id`, `action`, `backup_id`, `ip_address`, `user_agent`, `metadata` (object | null), `created_at` (ISO 8601 string)

**Meaningful `action` values today:** `backup.requested`, `backup.started`, `backup.completed`, `backup.failed`, `backup.config.updated`. Use them for filters or labels.

---

## 4. Mapping “use cases” to UI responsibilities

| Backend use case | User-facing idea |
|------------------|------------------|
| List backups | History table with filters, pagination, status chips. |
| Create backup | “Run backup now”: choose global vs domain, optional domain selector, optional notes; show spinner / long request warning. |
| Backup detail | Summary + file list + download actions + (future) restore. |
| Download file | Trigger save from blob; show human-readable size from `size_bytes`. |
| Restore | Placeholder only until API implements restore. |
| Get / update backup config | Settings panel: toggle periodic backups, interval (hours), display last/next run. |
| List audit | Activity log for compliance / debugging; link `backup_id` to detail route when present. |

---

## 5. Nuxt 3 integration suggestions (no markup)

1. **Composables:** e.g. `useAdminBackupApi()` wrapping typed `$fetch`/`ofetch` with shared `baseURL` and auth header from your existing auth store; separate functions: `listBackups`, `createBackup`, `getBackup`, `downloadBackupFile`, `getBackupConfig`, `updateBackupConfig`, `listBackupAudit`, `requestRestore` (expect 501).
2. **Types:** mirror TypeScript interfaces for `Backup`, `BackupFile`, `BackupConfig`, `BackupAuditRow`, and pagination payloads to avoid drift.
3. **Download helper:** centralise blob download + filename parsing so list and detail views reuse it.
4. **Routing:** e.g. `/admin/backups`, `/admin/backups/[id]`, `/admin/backups/settings` (config + short explanation of cron), `/admin/backups/audit` — or a tabbed single area; align with existing admin layout.
5. **Guards:** if the app already knows whether the user is Sudo Admin, **hide** backup nav entries for others to avoid useless 403s; otherwise handle 403 with a friendly “no access” state.
6. **Long requests:** `POST /backups` is synchronous; use loading state, disable double submit, consider optional progress copy (“Large datasets may take several minutes”).
7. **Empty states:** no backups yet; no files on failed run; explain automatic backups only run when server schedule is configured.
8. **i18n:** plan keys for scope, status, trigger, and audit actions for consistent translations.
9. **Error normalisation:** map 422 field errors to form messages; show `error` / `message` from JSON for 403/500/501.

---

## 6. Export file format (for tooltips / docs link)

The primary artifact is **JSON**: `reports_export.json` with a top-level `meta` object (`version`, `format`, `scope`, `domain_id`, `exported_at`) and a `reports` array of report records. Frontend does not need to parse it for a basic backup UI; optional “preview” features can document this separately.

---

## 7. What to tell the Cursor agent in one line

*“Integrate Nuxt 3 admin backup screens using Sanctum Bearer auth; call the endpoints in §2 with payloads in §3; restrict navigation to Sudo Admin; treat `POST /backups` as slow; implement download via blob; treat restore as coming soon (501); config uses `periodic_enabled` + `interval_hours`; audit uses `action` labels in §3.9.”*
