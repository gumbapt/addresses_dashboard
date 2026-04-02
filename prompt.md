# Implementation prompt: Backup, BackupFile, BackupConfig (and audit)

## 1. Architectural baseline (this codebase)

Follow the existing **vertical slice** used for admin features such as **Domain Groups**:

| Layer | Location | Responsibility |
|--------|----------|----------------|
| **Routes** | `routes/api.php` | `prefix('admin')`, middleware `auth:sanctum`, `admin.auth`; sensitive write routes often under `super.admin` middleware. |
| **Controller** | `app/Http/Controllers/Api/Admin/*Controller.php` | HTTP only: validation (`Validator` or `FormRequest`), call use cases, map entities/models to JSON, catch domain exceptions → status codes. Some controllers inject many use cases via constructor. |
| **Authorization** | `AdminFactory::createFromModel()`, `AuthorizeActionUseCase`, `CheckAdminPermissionUseCase` | Non–super-admin admins need role permissions; `SudoAdmin` entity bypasses checks in `AuthorizeActionUseCase`. Super-admin-only routes also use `super.admin` middleware. |
| **Use cases** | `app/Application/UseCases/<Context>/*UseCase.php` | One class per action; constructor-injected `*RepositoryInterface`; `execute(...)` with typed parameters; return **domain entities** (or primitives), not HTTP responses. |
| **Domain entity** | `app/Domain/Entities/*.php` | Immutable-style objects with `toArray()` / `toDto()` when patterns already exist. |
| **DTOs** (optional but common) | `app/Application/DTOs/...` | API / layer boundaries where already used (e.g. DomainGroup). |
| **Repository interface** | `app/Domain/Repositories/*RepositoryInterface.php` | Contract for persistence; no Eloquent here. |
| **Repository implementation** | `app/Infrastructure/Repositories/*Repository.php` | Eloquent models ↔ entity mapping (`toEntity`, etc.). |
| **Eloquent model** | `app/Models/*.php` | `$fillable`, casts, relationships, factories. |
| **DI registration** | `app/Providers/DomainServiceProvider.php` | `bind(Interface::class, Implementation::class)`. |
| **Migrations** | `database/migrations/` | New tables for `backups`, `backup_files`, `backup_configs`, and audit if stored in DB. |
| **Permissions** | `database/seeders/PermissionSeeder.php` + `RoleSeeder.php` | Add slugs (e.g. `backup-create`, `backup-read`, `backup-restore`, `backup-config-manage`) and assign **Sudo Admin** (`super-admin`) full set; tighten other roles as product requires. |
| **Tests** | `tests/Feature/Admin/*Test.php` | `RefreshDatabase`, seed `RoleSeeder`, `PermissionSeeder`, `AdminSeeder`, `AdminRolePermissionSeeder`; use Sanctum token from `Admin::where('is_super_admin', true)` or factory; assert JSON + `assertDatabaseHas`. |

**Note:** There is **no existing global “audit” module** in this repo (no `AuditLog` grep hits). Plan either a dedicated `backup_audit_logs` (or generic `admin_activity_logs`) table + repository, or a small `AuditLogger` service invoked from use cases.

---

## 2. Product goal

Implement **backup and restore** capabilities so a **Sudo Admin** can later restore application state from:

- A **global** backup (all domains), or  
- A **domain-scoped** backup (single `domains.id`).

Support **manual** backups (triggered by an admin) and **automatic** backups (scheduled via cron later). Persist **configuration** for periodic backups (interval + on/off). Record **who requested** a backup and **when**, for audit.

---

## 3. Domain model (proposed)

### 3.1 `Backup` (table e.g. `backups`)

Represent one logical backup run.

Suggested fields (adjust names to match project naming conventions):

- `id`
- `scope` — enum/string: `global` | `domain` (or nullable `domain_id`: null = global).
- `domain_id` — nullable FK → `domains.id`; required when scope is domain.
- `created_at` / `completed_at` (or single `backup_at` if you only store completion time).
- `trigger` — enum: `manual` | `automatic`.
- `requested_by_admin_id` — nullable FK → `admins.id`; null when `automatic`.
- `reports_count_at_backup` — unsigned integer; snapshot:
  - **Global:** count of `reports` across all domains (define whether soft-deleted / status filters apply).
  - **Domain:** count of `reports` for that `domain_id` only.
- `status` — enum: `pending` | `running` | `completed` | `failed` (extensible).
- `error_message` — nullable text (if failed).
- Optional: `checksum`, `notes`, `size_bytes` aggregate (sum of files).

Relationships:

- `belongsTo` Domain (optional), Admin (requested_by).
- `hasMany` BackupFile.

### 3.2 `BackupFile` (table e.g. `backup_files`)

Represent one on-disk artifact (or S3 object) belonging to a backup.

Suggested fields:

- `id`
- `backup_id` — FK → `backups.id`
- `disk` — string (e.g. `local`, `s3`) aligned with Laravel filesystem config.
- `path` — string (relative path or key).
- `original_filename` or logical label (e.g. `reports.sql`, `storage.zip`).
- `mime_type` — nullable
- `size_bytes` — unsigned big integer
- `created_at`

Rules: one Backup has many BackupFiles; deleting a Backup should be defined (cascade or restrict).

### 3.3 `BackupConfig` (table e.g. `backup_configs`)

Singleton or **one row per environment** (team choice: single row `id=1` is enough).

Suggested fields:

- `id`
- `periodic_enabled` — boolean (periodic backups on/off).
- `period_interval` — e.g. daily / weekly; implement as `enum` or `cron_expression` string, or `interval_hours` integer — pick one clear approach for the scheduler.
- `last_run_at` / `next_run_at` — nullable datetimes (optional but useful).
- `updated_by_admin_id` — nullable FK (who last changed config).
- `created_at`, `updated_at`

Only **Sudo Admin** (or role with `backup-config-manage`) may update this resource.

### 3.4 Audit (backup-specific)

Persist every **backup request** (and optionally config changes):

- `id`
- `admin_id` — nullable (system/cron may be null or a system user).
- `action` — e.g. `backup.requested`, `backup.started`, `backup.completed`, `backup.failed`, `backup.config.updated`.
- `backup_id` — nullable FK.
- `ip_address` / `user_agent` — optional, from request when HTTP-triggered.
- `metadata` — JSON (e.g. scope, domain_id, trigger).
- `created_at`

Alternatively, a generic `admin_audit_logs` table if you want reuse outside backups.

---

## 4. Behaviour

### 4.1 Creating a backup (use case)

1. Authorize caller (Sudo Admin or permitted role).
2. Resolve scope (global vs domain) and validate `domain_id` when needed.
3. Compute and store `reports_count_at_backup` (query `reports` / `Report` model).
4. Insert `Backup` row (`pending` or `running`).
5. Write **audit** row: requester + timestamp + scope.
6. Run actual export (implementation detail):
   - Could be queued job (`ShouldQueue`) for large datasets.
   - Produce one or more files; store via `Storage`; create `BackupFile` rows; update `Backup.status` and `completed_at`.

**Cron (future):** scheduled command reads `BackupConfig`; if `periodic_enabled`, dispatches the same use case with `trigger = automatic`, `requested_by_admin_id = null`.

### 4.2 Restore (scope for later; design now)

- API shape: e.g. `POST /api/admin/backups/{id}/restore` (super admin only).
- Use case must define what “restore state” means: DB rows for reports only? full DB? files?  
  Document in code comments; implement incrementally if needed.

### 4.3 Listing / download

- List backups (filter by scope, domain, status, date).
- Optional: signed URL or authenticated download for each `BackupFile`.

---

## 5. API surface (suggested)

Under `Route::middleware(['auth:sanctum', 'admin.auth'])` (and `super.admin` where appropriate):

- `GET /api/admin/backups` — list (paginated).
- `POST /api/admin/backups` — create manual backup (body: `scope`, optional `domain_id`).
- `GET /api/admin/backups/{id}` — detail + files metadata.
- `GET|PUT /api/admin/backup-config` — get/update periodic settings (Sudo Admin).
- `GET /api/admin/backup-audit` — optional, paginated audit for backups.

Wire **permissions** through `AuthorizeActionUseCase` for non-sudo paths if any admin-level ops are added later.

---

## 6. Implementation checklist (for the coding agent)

1. Migrations: `backups`, `backup_files`, `backup_configs`, audit table(s).
2. Eloquent models + factories.
3. Domain entities + optional DTOs.
4. `BackupRepositoryInterface` + `BackupRepository` (+ config/audit if split).
5. Use cases: e.g. `CreateBackupUseCase`, `ListBackupsUseCase`, `GetBackupByIdUseCase`, `UpdateBackupConfigUseCase`, `RecordBackupAuditUseCase`.
6. `BackupController` (admin API) + register routes.
7. `DomainServiceProvider` bindings.
8. `PermissionSeeder` / `RoleSeeder`: new slugs; **Sudo Admin** keeps full domain + backup permissions.
9. Feature tests: create backup (global + domain), forbidden for non-super when required, config update, audit rows asserted.
10. Document in README or internal doc: how to run periodic command and where files are stored.

---

## 7. Constraints

- Match existing **naming**, **JSON response** shapes (`success`, `data`, `message` where consistent), and **exception** handling patterns.
- Do not break existing `Report` / `Domain` flows; backup should be additive.
- Prefer **English** for code, comments, and user-facing API messages unless the project standard differs.

---

## 8. Success criteria

- Sudo Admin can trigger a **manual** backup (global or per-domain) and see persisted `Backup` + `BackupFile` rows and **report counts** at backup time.
- **Automatic** path is pluggable: same use case + cron reading `BackupConfig`.
- **Audit** records who requested (admin + time) for backup operations (and config changes if implemented).
- `BackupConfig` stores **period** and **enabled** flag for periodic backups.
- Test suite covers happy paths and authorization for new endpoints.

---

## 9. Appendix: SQL dump vs JSON export (restore strategy)

**SQL** (e.g. `mysqldump`): best for **full database** disaster recovery on the same engine; weak for **selective** per-domain restore without editing dumps; schema migrations can age old files poorly.

**JSON** (as in the shipped `reports_export.json`): best for **application-level**, scoped backups (global vs one domain), validation, and future **import** with custom merge rules; requires explicit import code and care for FKs and ids.

**Recommendation:** use **JSON** as the primary format for report-centric restore; add **SQL** only as a separate ops/infra concern (or a later “full DB snapshot” feature) if you need bare-metal recovery.
