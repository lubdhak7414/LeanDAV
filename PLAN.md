# PLAN.md — Lean PHP WebDAV Server

Here's the plan for building a zero-dependency PHP WebDAV server that works natively with both Windows File Explorer and Linux file managers, plus a management UI.

---

## 1. Overview

**Goal:** A single-directory PHP application (no Composer, no external libraries) that implements the WebDAV protocol (RFC 4918) well enough for native mounting on Windows and Linux, with a browser-based management dashboard.

**Host:** `https://s3.saf1.me`
**Auth:** Basic Auth — username `safwan`, password `abczmnm`
**Storage:** Flat filesystem under a `./data/` directory (relative to the app root).

---

## 2. Architecture

```
saf1.me/
├── index.php          ← Single entry point: routes ALL requests
├── lib/
│   ├── dav.php        ← WebDAV protocol engine (PROPFIND, PUT, GET, DELETE, MKCOL, MOVE, COPY, OPTIONS, LOCK, UNLOCK)
│   ├── auth.php       ← Basic Auth middleware
│   └── ui.php         ← HTML management dashboard renderer
├── data/              ← User files live here (the virtual filesystem root)
│   └── .gitkeep
└── .htaccess          ← Apache rewrite rules + force HTTPS
```

**Why this structure:**

- `index.php` is the single entry point — `.htaccess` rewrites everything to it
- `lib/dav.php` contains the full WebDAV verb handler — this is the core
- `lib/auth.php` is a one-function guard
- `lib/ui.php` renders the management HTML when accessed via browser
- No database. The filesystem IS the database.

---

## 3. Routing Logic (`index.php`)

```
Request comes in
  → .htaccess rewrites to index.php
  → index.php checks: is this a browser GET to "/"?
      YES → render management UI (lib/ui.php)
      NO  → treat as WebDAV request
            → authenticate (lib/auth.php, Basic Auth)
            → dispatch to appropriate handler in lib/dav.php based on HTTP method
```

**Detection of browser vs DAV client:**

- Browser: `GET /` with `Accept: text/html` → show UI
- DAV client: any `PROPFIND`, `MKCOL`, etc. → DAV engine
- Windows sends `User-Agent: Microsoft-WebDAV-MiniRedir/*`
- Linux GVFS sends `User-Agent: GVFS/*`
- Browsers send recognizable UA strings

---

## 4. WebDAV Protocol Engine (`lib/dav.php`)

This is the critical piece. Every method must return correct responses or Windows/Linux will refuse to mount.

### 4.1 Supported Methods

| Method      | Purpose                              | Critical for             |
| ----------- | ------------------------------------ | ------------------------ |
| `OPTIONS`   | Announce DAV compliance              | Both                     |
| `PROPFIND`  | Directory listing + file metadata    | Both (most critical)     |
| `PROPPATCH` | Set/change properties                | Both                     |
| `GET`       | Download file                        | Both                     |
| `HEAD`      | Metadata without body                | Both                     |
| `PUT`       | Upload/create file                   | Both                     |
| `DELETE`    | Delete resource                      | Both                     |
| `MKCOL`     | Create directory                     | Both                     |
| `MOVE`      | Move/rename                          | Both                     |
| `COPY`      | Copy resource                        | Both                     |
| `LOCK`      | Lock resource (exclusive write lock) | Windows strongly prefers |
| `UNLOCK`    | Release lock                         | Windows strongly prefers |

### 4.2 PROPFIND Response Format (the hardest part)

This is where 90% of WebDAV compatibility issues live. The XML response must include:

```xml
<?xml version="1.0" encoding="utf-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:href>/path/to/resource</D:href>
    <D:propstat>
      <D:prop>
        <D:displayname>filename.txt</D:displayname>
        <D:getcontentlength>1234</D:getcontentlength>
        <D:getlastmodified>RFC 2616 date</D:getlastmodified>
        <D:creationdate>ISO 8601 date</D:creationdate>
        <D:resourcetype><D:collection/></D:resourcetype>  <!-- or empty for files -->
        <D:getcontenttype>text/plain</D:getcontenttype>
        <D:getetag>"abc123"</D:getetag>
      </D:prop>
      <D:status>HTTP/1.1 200 OK</D:status>
    </D:propstat>
  </D:response>
</D:multistatus>
```

**Windows-specific requirements:**

- Must handle `Depth: 0`, `Depth: 1`, and `Depth: infinity`
- The `href` must be URL-encoded properly
- Must return the trailing `/` for collections in href
- Must include `<D:collection/>` inside `<D:resourcetype>` for directories
- Date format must be RFC 2616 (e.g., `Tue, 15 Nov 1994 08:12:31 GMT`)
- Must handle `PROPFIND` requesting all props (`<D:allprop/>`) and specific props (`<D:prop>`)
- Windows sends PROPFIND with `<D:allprop/>` most of the time

**Linux/GVFS requirements:**

- Similar to above but slightly more forgiving
- Also expects proper `DAV:` namespace

### 4.3 LOCK / UNLOCK (Simplified Implementation)

Windows File Explorer attempts LOCK before every write. Without it, some operations silently fail or show errors.

**Strategy:** In-memory (file-based) lock tracking. Not persistent across server restarts — that's fine for a single-user server.

- `LOCK` → store lock token in `./data/.locks/` as JSON files
- `UNLOCK` → remove lock file
- Lock timeout → default 600 seconds (10 minutes)
- Support exclusive write locks only
- Return proper `lockdiscovery` XML in response

### 4.4 Authentication (`lib/auth.php`)

```php
// Send 401 with WWW-Authenticate: Basic realm="saf1.me"
// Validate: username === 'safwan' && password === 'abczmnm'
// Both Windows and Linux mount dialogs support Basic Auth
// MUST be over HTTPS (enforced by .htaccess redirect)
```

### 4.5 MIME Type Detection

Use PHP's `mime_content_type()` with fallback to a hardcoded map of common extensions. Windows and Linux both use the content type to determine file icons.

### 4.6 Chunked / Large File Uploads

- Windows sometimes sends `Transfer-Encoding: chunked` for large PUT
- PHP reads `php://input` which handles this transparently
- No special handling needed beyond reading the full input stream

---

## 5. Management UI (`lib/ui.php`)

When accessed via browser (not a DAV client), render a clean management dashboard.

### 5.1 Design Direction

**Aesthetic:** Brutalist-minimal. Dark background (`#0a0a0a`), monospace type, sharp borders. The vibe of a server control panel — no fluff.

**Font:** JetBrains Mono (via Google Fonts CDN)
**Colors:** Near-black bg, warm white text, single amber accent (`#e8a530`)

### 5.2 Features

| Feature        | Description                         |
| -------------- | ----------------------------------- |
| File browser   | Tree/list view of `/data/` contents |
| Upload         | Drag-and-drop zone + file picker    |
| New folder     | Modal with name input               |
| Delete         | Confirmation dialog, then delete    |
| Download       | Direct link to file                 |
| Breadcrumb nav | Navigate directories                |
| File info      | Size, type, last modified           |
| Usage stats    | Total files, total size             |

### 5.3 Implementation

- All UI actions hit the same `index.php` with query params (e.g., `?action=upload`, `?action=delete&path=foo.txt`)
- The UI uses the same auth session (browser cookie or re-prompt)
- No JavaScript framework — vanilla JS only for drag-and-drop and fetch calls
- Responsive but optimized for desktop

---

## 6. `.htaccess` Configuration

```apache
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Route everything to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Disable directory listing (we handle it via DAV)
Options -Indexes

# Set correct charset
AddDefaultCharset UTF-8
```

**Note:** If using Nginx instead of Apache, equivalent `nginx.conf` location blocks will be documented in the plan.

---

## 7. Compatibility Matrix

| Client                      | Auth          | Browse   | Upload     | Download | Delete     | Rename     | Move |
| --------------------------- | ------------- | -------- | ---------- | -------- | ---------- | ---------- | ---- |
| Windows 10/11 File Explorer | Basic         | PROPFIND | PUT        | GET      | DELETE     | MOVE       | MOVE |
| Linux Nautilus (GVFS)       | Basic         | PROPFIND | PUT        | GET      | DELETE     | MOVE       | MOVE |
| Linux davfs2                | Basic         | PROPFIND | PUT        | GET      | DELETE     | MOVE       | MOVE |
| macOS Finder                | Basic         | PROPFIND | PUT        | GET      | DELETE     | MOVE       | MOVE |
| CyberDuck / WinSCP          | Basic         | PROPFIND | PUT        | GET      | DELETE     | MOVE       | MOVE |
| Browser (management UI)     | Session/Basic | PHP scan | PHP upload | PHP read | PHP unlink | PHP rename | —    |

---

## 8. Security Considerations

1. **HTTPS mandatory** — `.htaccess` forces redirect; Basic Auth sends credentials in base64 (trivially decoded without TLS)
2. **Path traversal protection** — all paths resolved relative to `./data/`, reject any path containing `..`
3. **PHP `open_basedir`** — set to app directory in `.user.ini` if possible
4. **No PHP execution in data dir** — `.htaccess` in `./data/` disables PHP engine
5. **Single user** — hardcoded credentials, no registration
6. **File size limit** — configurable, default 100MB (matches PHP `upload_max_filesize`)

---

## 9. File Structure Summary

```
saf1.me/
├── .htaccess                 ← Apache rewrite + HTTPS enforcement
├── index.php                 ← Entry point (~30 lines: route, auth, dispatch)
├── lib/
│   ├── dav.php               ← WebDAV engine (~400-500 lines: all DAV methods)
│   ├── auth.php              ← Basic auth guard (~20 lines)
│   ├── mime.php              ← MIME type map (~60 lines)
│   └── ui.php                ← Management dashboard HTML/CSS/JS (~300 lines)
├── data/
│   ├── .gitkeep
│   └── .htaccess             ← Deny PHP execution in data dir
└── PLAN.md                   ← This document
```

**Total estimated:** ~800-1000 lines of PHP, zero external dependencies.

---

## 10. Implementation Order

| Phase | Task                                               | Est. Lines |
| ----- | -------------------------------------------------- | ---------- |
| 1     | `index.php` — routing + auth integration           | ~40        |
| 2     | `lib/auth.php` — Basic Auth                        | ~25        |
| 3     | `lib/mime.php` — MIME type detection               | ~70        |
| 4     | `lib/dav.php` — Core: OPTIONS, PROPFIND, GET, HEAD | ~200       |
| 5     | `lib/dav.php` — Write: PUT, DELETE, MKCOL          | ~120       |
| 6     | `lib/dav.php` — Move/Copy: MOVE, COPY              | ~80        |
| 7     | `lib/dav.php` — Locking: LOCK, UNLOCK              | ~100       |
| 8     | `.htaccess` + data dir protections                 | ~20        |
| 9     | `lib/ui.php` — Management dashboard                | ~300       |
| 10    | Testing with Windows + Linux clients               | manual     |

---

## 11. Testing Checklist

- [ ] `curl -X OPTIONS https://s3.saf1.me/` → returns DAV headers
- [ ] `curl -u safwan:abczmnm -X PROPFIND https://s3.saf1.me/` → valid XML
- [ ] Windows: Map network drive → `https://s3.saf1.me` → browse, upload, delete
- [ ] Linux: `gio mount dav://s3.saf1.me` → browse, upload, delete
- [ ] Browser: open `https://s3.saf1.me` → management UI loads
- [ ] Upload 50MB file via Windows → completes
- [ ] Create/rename/delete folder via Linux Nautilus
- [ ] Lock a file via Windows → edit → unlock
