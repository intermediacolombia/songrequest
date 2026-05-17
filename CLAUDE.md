# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- **Backend:** Vanilla PHP 8.x — no framework (no Laravel, Symfony, etc.)
- **Database:** MySQL 8.0 via PDO (`$GLOBALS['pdo']` singleton initialized in `inc/config.php`)
- **Frontend:** jQuery + Bootstrap 5.3.3 + SweetAlert2 + DataTables 1.13.6 + Font Awesome
- **No build tools** — no npm, composer, webpack, Vite, or TypeScript. All JS/CSS loaded from CDN or local files.
- **Deployment target:** Apache shared hosting at `https://songrequest.intermediacolombia.com`

## Development

There are no build, lint, or test scripts. Development is direct file editing; changes are tested by loading pages in a browser against a live or local MySQL database.

To set up locally, point Apache's DocumentRoot to this directory and create a MySQL database matching the schema in `attachments/background/intermed_news.sql`. Update the credentials in `inc/config.php`.

## Architecture

### Entry Points

- `index.php` — Public-facing song request form
- `admin/login/index.php` — Admin login
- `admin/canciones/index.php` — Admin dashboard (DataTables, state management)

### Global Configuration (`inc/config.php`)

Defines DB credentials, `URLBASE`, `SITE_TITLE`, timezone (`America/Bogota`), and loads all system settings from the DB into `$GLOBALS['SYS_SETTINGS']`. Every PHP file includes this via `require_once`. Helper functions `getSetting($key)` and `setSetting($key, $value)` read/write the `system_settings` table.

### AJAX Controllers (`controller/`)

Stateless PHP endpoints called by the public form via Fetch API:

| File | Purpose | Returns |
|---|---|---|
| `guardar.php` | Save new request (POST) | JSON |
| `listar.php` | User's request history | HTML |
| `contador.php` | Available request count | HTML |
| `sugerencias.php` | Autocomplete (artist/song) | JSON |
| `generos_activos.php` | Active genres list | JSON |
| `estado_formulario.php` | Form enabled/disabled status | JSON |
| `tiempo_estimado.php` | Queue time estimate | JSON |

### Admin Actions (`admin/canciones/`)

| File | Purpose |
|---|---|
| `actions.php` | CRUD on requests: `listar`, `estado`, `eliminar`, `comentar`, `bloqueo` |
| `masive_actions.php` | Bulk operations |
| `toggle_form.php` | Enable/disable public form |
| `update_generos.php` | Block/unblock genres |
| `update_setting.php` | Update system settings |
| `auto_match.php` | Automatic request matching logic |

### Request Lifecycle

```
User submits → guardar.php inserts (estado='Pendiente', bloqueada=1)
→ Admin sets estado='Programada' (unblocks user's slot)
→ Admin sets estado='Sonada' (played) or 'Rechazada' (rejected)
```

The `bloqueada` flag on a request controls whether the request occupies the user's submission quota. A request must be in 'Sonada' or 'Rechazada' state (or unblocked manually) for the user to submit again.

**Admission checks in `guardar.php` (in order):**
1. Per-device limit: `COUNT(*) WHERE cookie_id=? AND estado='Pendiente'` vs. `limite_solicitudes` (default 3)
2. Global capacity: `COUNT(*) WHERE estado IN ('Pendiente','Programada')` vs. `cupo_global` (default 10) — returns `status:'cupo_lleno'`
3. Duplicate detection: same song+artist in any state

### Device Identification

Users are tracked by a 32-char hex cookie (`cookie_id`) set in `index.php` with a 30-day expiry. This is how per-device request limits are enforced. No login for public users.

### Authentication (Admin)

- bcrypt passwords via `password_verify()`
- Session-based with remember-me tokens in `user_tokens` table (30-day expiry)
- 5-attempt lockout stored on `usuarios.intentos`
- RBAC: `usuarios` → `roles` → `role_permissions` → `permissions`
- Session validated on every admin page via `require_once '../login/session.php'`

## Key Conventions

- **SQL:** Always use PDO prepared statements — `$pdo->prepare()` + `execute([...])`. Never interpolate user input into queries.
- **Output:** Use `htmlspecialchars()` when echoing user-supplied data into HTML.
- **Settings:** Use `getSetting()` / `setSetting()` for all reads/writes to `system_settings`, never query the table directly.
- **Responses:** Admin AJAX endpoints return JSON with at minimum `{"status": "success"|"error", "message": "..."}`.
- **Frontend polling:** The public form polls `generos_activos.php` and `listar.php` every 5 seconds via `setInterval`.
- **Dark theme:** The public UI uses CSS variables and glassmorphism; the admin panel supports dark/light toggle stored in `localStorage`.
