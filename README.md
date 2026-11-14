# DB Studio — MyBB Plugin

Adds a permission-protected database studio to the Admin CP. Access is controlled from the administrator permissions screen.

DB Studio embeds [Adminer](https://www.adminer.org/) 6.1.2 inside a MyBB Admin CP page, so an administrator can browse tables, run SQL, edit rows, import and export — without a separate phpMyAdmin install. Unlike a plain Adminer upload, it is gated behind MyBB's own administrator permissions, an optional UID allow-list, an optional IP/CIDR allow-list, and a short-lived database-manager session.

**This plugin gives full read/write access to the MyBB database.** Treat it as a privileged tool: restrict it by IP, keep the allowed administrator list short, and deactivate it when it is not needed.

## Features

- **ACP menu entry** under *Tools → DB Studio*, registered as a standard MyBB admin module.
- **Administrator permission** — `Can manage the database with DB Studio`, granted per administrator group on the Admin Permissions screen. Super administrators always pass.
- **Explicit session gate** — after the permission checks, the administrator must click *Open DB Studio* to create a database-manager session (CSRF-protected with the MyBB post key).
- **Optional UID allow-list** — restrict access further to specific administrator user IDs.
- **Optional IP allow-list** — one IPv4/IPv6 address or CIDR range per line, with `#` comments. Empty means any IP.
- **Hardened session cookie** — 64 hex characters, `HttpOnly`, `SameSite=Lax`, stored server-side only as a SHA-256 hash.
- **Optional IP binding** — invalidate the session if the client IP changes.
- **Idle timeout** — default 15 minutes; `0` disables it.
- **One session per administrator** — opening DB Studio closes any earlier session for the same account.
- **Audit log** — records session starts, logouts, expired sessions, and each denial reason (`denied_disabled`, `denied_user`, `denied_ip`), with configurable retention.
- **Embedded Adminer 6.1.2** in an iframe, branded as DB Studio, with `X-Frame-Options: SAMEORIGIN` and a `frame-ancestors 'self'` CSP.
- **Three themes** — Auto (browser preference), Light, Dark — switchable from a toolbar and remembered in a cookie.
- **Two database login modes** — Automatic (reuse the credentials from `inc/config.php`) or Manual (show Adminer's login form).
- **PluginLibrary integration** — templates registered from `Templates/`, front-end stylesheet cached, settings installed from `settings.json`, all reversible on uninstall.
- **Respects a custom `$config['admin_dir']`** when installing the admin module and the admin-side stylesheet.
- `.htaccess` and `index.html` guards ship with the plugin folder.

## Requirements

| Requirement | Version / note |
| --- | --- |
| MyBB | 1.8.x (`compatibility: 18*`) |
| PHP | 7.4+ for the plugin; the bundled Adminer 6.1.2 may require a newer PHP — check [adminer.org](https://www.adminer.org/) |
| Database | MySQL / MariaDB (bundled Adminer also contains PostgreSQL, SQLite, MS SQL, Oracle and Elasticsearch drivers; the automatic login only maps MyBB's `mysql`, `mysqli`, `pgsql`, and `sqlite` config types) |
| PluginLibrary | Bundled — `inc/plugins/pluginlibrary.php` (v13, LGPL-3.0+, © Andreas Klauer) |
| Adminer | Bundled — `inc/plugins/DBStudio/lib/dbstudio.php` (6.1.2, Apache-2.0, © Jakub Vrana) |

## Installation

1. Extract the repository into your MyBB root (the directory containing `index.php`).
2. **Admin CP → Configuration → Plugins** → **DB Studio** → **Install**, then **Activate**.
3. **Users & Groups → Admin Permissions** → select the administrator group → **DB Studio** → grant *Can manage the database with DB Studio*.
4. **Configuration → Settings → DB Studio** and configure the switch, the allow-lists, the login mode, and the session/audit options.
5. **Tools → DB Studio** → *Open DB Studio*.

Installation needs write access to `admin/modules/` (or your configured `$config['admin_dir']`) and to each `admin/styles/<theme>/` directory, because activation copies the module files and the admin stylesheet into place. If those copies fail, the menu item or its styling will be missing — check directory permissions.

### Install / activate / deactivate / uninstall

- **Install** creates `dbstudio_sessions` and `dbstudio_log`, registers the 10 settings, registers the three templates, caches `dbstudio.css`, and copies the admin module plus `dbstudio.css` into every admin theme.
- **Activate** repeats the install work, refreshes the templates and stylesheet, and rebuilds the settings cache.
- **Deactivate** removes the cached stylesheet, the admin module, and the admin theme stylesheets. Tables, settings, and templates are left in place.
- **Uninstall** removes the settings group, templates, cached stylesheet, admin module, and admin theme stylesheets, then drops both tables.

## Repository layout

```
inc/plugins/dbstudio.php                          # bootstrap: info, install/activate wrappers, admin hooks
inc/plugins/pluginlibrary.php                     # bundled PluginLibrary (LGPL-3.0+)
inc/plugins/DBStudio/install.php                  # schema, settings, templates, stylesheet, admin module install
inc/plugins/DBStudio/database.sql                 # 2 tables
inc/plugins/DBStudio/settings.json                # the 10 settings (PluginLibrary)
inc/plugins/DBStudio/.htaccess                    # deny direct web access (Apache)
inc/plugins/DBStudio/Functions/admin_functions.php    # permission, allow-list, config credentials
inc/plugins/DBStudio/Functions/session_functions.php  # session, IP matching, audit log, pruning
inc/plugins/DBStudio/Functions/display_functions.php  # gate and iframe rendering
inc/plugins/DBStudio/Admin/dbstudio.php           # ACP controller: gates, themes, Adminer bootstrap
inc/plugins/DBStudio/Admin/Module/module.php      # admin module router; copied as admin/modules/dbstudio/dbstudio.php
inc/plugins/DBStudio/Admin/Module/module_meta.php # menu, action handler, permission; copied as module_meta.php
inc/plugins/DBStudio/Assets/dbstudio.css          # ACP page + iframe toolbar styles (cached via PluginLibrary)
inc/plugins/DBStudio/Assets/dbstudio_theme.css    # Adminer theme base
inc/plugins/DBStudio/Assets/dbstudio_theme_light.css
inc/plugins/DBStudio/Assets/dbstudio_theme_dark.css
inc/plugins/DBStudio/Templates/dbstudio_gate.html    # "Open DB Studio" session gate
inc/plugins/DBStudio/Templates/dbstudio_denied.html  # hard denial page
inc/plugins/DBStudio/Templates/dbstudio_frame.html   # theme toolbar + iframe
inc/plugins/DBStudio/lib/dbstudio.php             # bundled Adminer 6.1.2 (Apache-2.0)
admin/modules/dbstudio/dbstudio.php               # pre-built copy, also written by activate
admin/modules/dbstudio/module_meta.php            # pre-built copy, also written by activate
README.md
LICENSE                                           # GPL-3.0 for the plugin
LICENSE-ADMINER                                   # Apache-2.0 for the bundled Adminer
```

`admin/modules/dbstudio/` is included so the repository layout is complete, but those two files are generated copies: activation overwrites them from `inc/plugins/DBStudio/Admin/Module/` and deactivation deletes them. If you use a custom `$config['admin_dir']`, the files are written there instead, and the copies in `admin/` are unused.

Template keys are registered under the `dbstudio` prefix, producing `dbstudio_gate`, `dbstudio_denied`, and `dbstudio_frame` in the database.

## Database

| Table | Purpose |
| --- | --- |
| `dbstudio_sessions` | Live database-manager sessions: hashed session id, administrator UID, IP, creation time, last activity. |
| `dbstudio_log` | Audit trail: UID, action (`start`, `logout`, `expired`, `denied_disabled`, `denied_user`, `denied_ip`), IP, timestamp. |

Both tables are InnoDB/`utf8mb4`. Schema lives in `database.sql` and is applied by splitting the file on `;` line endings, with `{PREFIX}` replaced by `TABLE_PREFIX`.

## Settings (10)

| Setting | Default | Description |
| --- | --- | --- |
| Enable DB Studio | `1` | Master switch. When off, every request is refused, including for administrators with permission. |
| Additional Allowed Administrator UIDs | *(empty)* | Comma/space-separated administrator UIDs. Empty = no UID restriction. See the caveat below. |
| Allowed IP Addresses | *(empty)* | One IPv4/IPv6 address or CIDR range per line. Empty = every IP. `#` starts a comment line. |
| Database Login Mode | `1` (Automatic) | Automatic reuses the credentials from `inc/config.php`; Manual shows Adminer's login form. |
| DB Studio Theme | `auto` | `auto`, `light`, or `dark`; the toolbar can override per administrator. |
| Bind Session To IP Address | `1` | Destroy the session when the client IP changes. |
| Idle Timeout (Minutes) | `15` | Close the session after this much inactivity; `0` disables the timeout. |
| One Session Per Administrator | `1` | New sessions delete older sessions for the same UID. |
| Write Audit Log | `1` | Record session and denial events in `dbstudio_log`. |
| Audit Log Retention (Days) | `90` | Prune older log rows; `0` keeps everything. |

Settings are stored as `dbstudio_<key>`.

**Caveat on the UID allow-list.** The administrator permission is checked first, and `is_super_admin()` always passes. For a non-super administrator, the allow-list can only *narrow* access to the listed UIDs — it cannot grant DB Studio to an administrator who lacks the *Can manage the database with DB Studio* permission. Leave the setting empty to let every permitted administrator in.

## How it works

- `admin_tools_action_handler` registers a legacy `tools&action=dbstudio` action alongside the standard top-level module.
- `admin_load` detects either `module=dbstudio&action=dbstudio` or `tools&action=dbstudio`, loads `Admin/dbstudio.php`, and exits before MyBB renders the normal page.
- The controller runs, in order: `check_admin_permissions()` → master switch → UID allow-list → IP allow-list → session check.
- With no valid session, a gate page is rendered. Submitting it with a valid post key calls `dbstudio_session_create()`, which stores the SHA-256 of a random 256-bit token and sets the `dbstudio_sid` cookie.
- Theme switching is handled by `?dbstudio_theme=auto|light|dark`, stored in a one-year cookie. `?dbstudio_theme_css=1&theme=…` streams the combined Adminer theme CSS with an immutable cache header.
- The normal page renders `dbstudio_frame.html` with a theme toolbar and an iframe pointing at `index.php?module=dbstudio&dbstudio_frame=1`.
- Inside the frame, the controller defines `MYBB_DBSTUDIO_SERVE`, loads `lib/dbstudio.php` (Adminer), and exits. In Automatic mode it injects the config credentials into the Adminer login request and disables Adminer's login-token check; Manual mode leaves the login form visible.
- Session checks update `lastactive`; roughly one request in fifty also prunes stale sessions and expired audit rows.

## Notes and known limitations

- **Full database control.** Anyone who gets through the gates can run arbitrary SQL with the credentials in `inc/config.php` (Automatic mode) — including `DROP TABLE`, user changes, and reading every private message. The MyBB administrator password alone is the primary factor; there is no second factor in this plugin.
- **Adminer phones home.** Bundled Adminer performs its own version check against `adminer.org` and can render a version iframe from that host. This is a third-party request originating from the Admin CP; there is no plugin setting to disable it.
- **Automatic login mode bypasses Adminer's login form** and its login-token verification by design. Switch to Manual if you want the database password typed per session.
- **`.htaccess` is Apache-only.** On nginx or LiteSpeed the deny rules are ignored, but `lib/dbstudio.php` still refuses to run unless `MYBB_DBSTUDIO_SERVE` is defined, so direct access to the bundled Adminer does not execute.
- **No language files ship with this plugin.** Every ACP string is hardcoded in PHP or in the templates, so the interface is English-only and cannot be localised through `inc/languages/`.
- **Admin module files are copied into the admin directory** on install/activate and deleted on deactivate. A read-only `admin/modules/` or `admin/styles/` tree will leave DB Studio missing from the menu or unstyled.
- **The module can exist while the plugin is installed but not active.** The admin module is copied by `dbstudio_install_run()` as well as `dbstudio_activate_run()`, so the *Tools → DB Studio* entry may appear before activation; the gate then fails against the missing tables. Activate the plugin to use it.
- **The gate and frame templates are fetched with `$templates->get()` at runtime**, not listed in `$templatelist`. If a cache rebuild leaves them out, the fallback is a plain ACP error page.
- **The admin stylesheet is copied into every admin theme**, not registered as a theme asset. Adding a new admin theme later requires re-activating the plugin (or re-running the copy) for DB Studio to be styled on that theme.
- **Custom `$config['admin_dir']` is respected**, but the pre-built `admin/modules/dbstudio/` files in this repository are only correct for the default `admin` directory.

## Author

**PARAD0X** — [MyBB Community profile](https://community.mybb.com/user-134311.html)

- Website: https://mybb.epizy.com
- Version: 1.0.0

## License

The plugin is licensed under **GPL-3.0** — see [LICENSE](LICENSE).

The bundled Adminer library (`inc/plugins/DBStudio/lib/dbstudio.php`, © 2007 Jakub Vrana, version 6.1.2) is dual-licensed under Apache-2.0 or GPL-2.0; this repository uses it under **Apache-2.0** — see [LICENSE-ADMINER](LICENSE-ADMINER). Its copyright and license header is retained in the file.
