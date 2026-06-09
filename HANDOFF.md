# Handoff — WebDAV Management UI

## What This Is

A self-contained PHP WebDAV server with a browser-based file management dashboard. The UI (`lib/ui.php`) is a single-file frontend that handles uploads, downloads, folder creation, renaming, and deletion — all through a dark-themed, monospace-styled interface.

## Project Structure

```
huda/
├── public/
│   └── index.php          # Entry point — routes to WebDAV or UI based on client
│   └── .htaccess          # Apache rewrite rules
├── lib/
│   ├── ui.php             # ← THE FILE — Management dashboard (1428 lines)
│   ├── dav.php            # WebDAV protocol handler (PROPFIND, PUT, DELETE, etc.)
│   ├── auth.php           # Basic auth (single user from config)
│   ├── util.php           # Path resolution, client detection, config loading
│   ├── mime.php           # MIME type detection (fileinfo + extension fallback)
│   ├── range.php          # HTTP Range request handling
│   ├── lock.php           # WebDAV locking (exclusive locks)
│   ├── log.php            # Request logging with auto-rotation
│   ├── error.php          # XML WebDAV error responses
│   └── compat.php         # PHP compatibility shims
├── config.example.php     # Config template (copy to config.php)
├── data/                  # Storage root (gitignored contents)
├── Dockerfile             # PHP 8.2 + Apache
├── docker-compose.yml
├── nginx.conf.example
└── README.md
```

## How Routing Works

`public/index.php` is the single entry point:

```
Browser GET with text/html  →  require_auth() → handle_ui_request()  → UI dashboard
Browser POST with ?action=  →  require_auth() → handle_ui_action()   → Upload/delete/etc.
Everything else (WebDAV)    →  require_auth() → handle_dav_request() → PROPFIND/PUT/DELETE/etc.
```

The UI is **not** a separate app — it shares the same auth, same storage, same entry point as the WebDAV server. A macOS Finder or Windows Explorer mounting the WebDAV URL hits the same backend.

## Config (config.php — not committed)

```php
return [
    'auth' => ['username' => 'admin', 'password' => '...'],
    'storage_path'    => __DIR__ . '/data/',
    'max_upload_size' => 104857600,  // 100MB
    'lock_dir'        => __DIR__ . '/data/.locks/',
    'lock_timeout'    => 600,
    'hide_dotfiles'   => true,
    'log_level'       => 'info',     // 'debug'|'info'|'error'|'none'
    'log_dir'         => __DIR__ . '/data/.logs/',
    'max_log_size'    => 10485760,   // 10MB auto-rotate
    'depth_infinity_cap' => 1000,
];
```

## Recent Improvements to `lib/ui.php` (June 2025)

### Security

| Feature | Details |
|---|---|
| **CSRF tokens** | Session-based 64-char hex tokens. All mutating actions (upload, delete, mkdir, rename) require `csrf_token` in POST body or `X-CSRF-Token` header. |
| **Upload error mapping** | Raw PHP `UPLOAD_ERR_*` codes replaced with human-readable messages (e.g., "File exceeds server upload limit" instead of "1"). |
| **Rename sanitization** | `sanitize_name()` strips null bytes, path separators, and Windows-reserved names (CON, NUL, COM1-9, LPT1-9). |
| **Path decoding** | `$_GET['path']` now goes through `rawurldecode()` before use. |

### UX / JavaScript

| Feature | Details |
|---|---|
| **Real upload progress** | Switched from `fetch()` to `XMLHttpRequest` — progress bar now shows actual bytes transferred, not a fake bar. |
| **Upload queue** | Multiple files upload sequentially with "Uploading 2 of 5: photo.jpg (67%)" feedback. |
| **Enter submits modals** | Pressing Enter in the New Folder or Rename modal triggers the action (no need to click the button). |
| **File input reset** | After upload, the file input resets so selecting the same file again works. |
| **Client-side sorting** | Name / Size / Date toggle buttons. Directories always sort first. |
| **Live search/filter** | Filter input instantly hides non-matching rows — no page reload needed. |
| **Drag-to-upload on file list** | The entire file list area accepts drag-and-drop, not just the sidebar drop zone. |

### Visual

| Feature | Details |
|---|---|
| **"Up" button** | Arrow button before breadcrumb (hidden at root). |
| **File type icons** | 30+ extensions mapped to contextual emoji: 🖼️ images, 🎬 video, 🎵 audio, 📦 archives, 🔧 code, 📕 documents, 📄 text. |
| **Mobile layout** | Actions always visible, date column hidden on small screens, grid properly collapses. |
| **Action buttons** | Opacity 0 → 1 on row hover (cleaner look, actions appear on demand). |

### Code Quality

| Feature | Details |
|---|---|
| **Logging** | All UI actions (download, upload, mkdir, delete, rename) now write to `webdav.log` via `log_request()`. |
| **Combined scan + stats** | Single loop computes file count, total size, and builds `entry_meta[]` — no redundant directory re-scan. |
| **JSON for JS values** | `json_encode()` for `currentPath`, `csrfToken`, `maxUploadSize` — prevents injection via config values. |

### Functions Added to `ui.php`

```php
ui_csrf_token(): string          // Generate/return CSRF token (session-backed)
ui_csrf_validate(string): bool   // Validate CSRF token
upload_error_message(int): string // Human-readable upload error
sanitize_name(string): string|false // Sanitize rename/new-name input
ui_resolve_get_path(string): string // rawurldecode wrapper
file_type_icon(string, bool): string // Extension → emoji icon
```

## Architecture Notes

- **Single-user auth** — Basic HTTP auth, one username/password from config. No sessions for WebDAV (stateless), but sessions exist for CSRF in the UI.
- **No database** — Everything is filesystem-based. File listing is `scandir()`, metadata is `filesize()`/`filemtime()`.
- **No JS build step** — All CSS and JS are inline in the PHP template. No bundler, no framework.
- **PHP 8.1+ required** — Uses `str_contains()`, union types (`string|false`), named arguments.
- **WebDAV clients** — Tested with macOS Finder, Windows MiniRedir, GVFS (Linux), rclone, Cyberduck.

## Known Limitations / Open Questions

1. **No multi-user support** — Single auth pair. Adding users would require a session/auth system overhaul.
2. **No HTTPS enforcement** — Relies on reverse proxy (nginx/Apache) for TLS.
3. **No file preview** — Images/text files download rather than preview in-browser.
4. **No batch operations** — Can't select multiple files and delete/move them at once.
5. **No move/copy action** — Only rename exists; no drag-and-drop reordering or copy.
6. **Session dependency for CSRF** — If PHP sessions are broken or cookieless, CSRF fails. Consider falling back to double-submit cookie pattern.
7. **Upload progress for very large files** — XHR progress events are accurate, but server-side `upload_max_filesize` may silently truncate.
8. **No undo for delete** — Deletions are permanent (no trash/recycle bin).

## What I'd Like Feedback On

1. **Security** — Is the CSRF implementation solid? Any gaps in the escaping chain? Is the path traversal protection sufficient?
2. **UX** — Does the sort/filter/upload flow feel intuitive? Is the mobile layout usable?
3. **Code organization** — Should the 1400-line `ui.php` be split further (e.g., separate CSS/JS files)? Or is inline acceptable for a single-file deployment?
4. **Missing features** — Of the "Known Limitations" above, which ones are must-haves vs. nice-to-haves?
5. **Performance** — The combined scan+stats loop is better, but for directories with thousands of files, should we paginate?
6. **Accessibility** — Any ARIA labels, focus management, or keyboard navigation improvements needed?
