# PLAN.md — Lean PHP WebDAV Server (v3)

## 0. Changelog

| Version | Date | Changes                                                                                                                                                                                                                                                                                                          |
| ------- | ---- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| v1      | —    | Initial plan: single-directory structure, 15 sections                                                                                                                                                                                                                                                            |
| v2      | —    | Restructured to `public/` web root + external `data/`. Added config.php, Docker, Nginx config, stream-based I/O, lock garbage collection, PROPPATCH dummy handler                                                                                                                                                |
| v3      | —    | Added macOS quota fix, `MS-Author-Via` header, recursive DELETE, inode-free ETags, HTTP Range support, 5 new modules (`log.php`, `error.php`, `util.php`, `range.php`, `compat.php`), connection abort handling, atomic PUT, XMLWriter for PROPFIND, Depth infinity cap, security hardening, assumptions section |

---

## 1. Overview

**Goal:** A zero-dependency PHP application implementing the WebDAV protocol (RFC 4918) for native mounting on Windows File Explorer, Linux file managers, and macOS Finder — plus a browser-based management dashboard.

**Host:** `https://s3.saf1.me`
**Auth:** Basic Auth — configurable via `config.php`
**Storage:** Filesystem-based, configurable path (defaults to `./data/` adjacent to the web root)

### 1.1 Assumptions & Constraints

| Constraint            | Detail                                                                                                            |
| --------------------- | ----------------------------------------------------------------------------------------------------------------- |
| **Zero dependencies** | No Composer, no npm, no external PHP libraries. Only PHP core extensions (`fileinfo`, `xmlwriter`, `dom`, `json`) |
| **PHP 8.0+**          | Uses typed properties, `match` expressions, named arguments. `xmlwriter` extension required for PROPFIND          |
| **Single-user**       | One username/password pair. No multi-tenancy, no user registration                                                |
| **HTTPS-only**        | Basic Auth sends credentials in base64. TLS is mandatory, not optional                                            |
| **Single-server**     | No clustering, no shared storage. Lock state is file-based and ephemeral                                          |
| **WebDAV Class 1+2**  | Full read/write + locking. Not a full Class 3 server (no versioning)                                              |

---

## 2. Directory Structure

```
saf1.me/
├── public/                     ← Web root (Apache/Nginx points here)
│   ├── .htaccess               ← Rewrite rules + HTTPS enforcement
│   └── index.php               ← Single entry point (~40 lines)
├── lib/
│   ├── dav.php                 ← WebDAV protocol engine (~500 lines)
│   ├── auth.php                ← Basic Auth middleware (~25 lines)
│   ├── lock.php                ← LOCK/UNLOCK + garbage collection (~80 lines)
│   ├── mime.php                ← MIME type detection (~70 lines)
│   ├── ui.php                  ← Management dashboard HTML/CSS/JS (~350 lines)
│   ├── log.php                 ← Structured request/response logging (~40 lines)
│   ├── error.php               ← Centralized XML error responses (~30 lines)
│   ├── util.php                ← Shared helpers: path sanitization, client detection, config loading (~50 lines)
│   ├── range.php               ← HTTP Range request parsing + 206 responses (~60 lines)
│   └── compat.php              ← Client-specific quirks: macOS quota skip, Windows trailing slash, Depth cap (~40 lines)
├── data/                       ← User files (OUTSIDE web root — not web-accessible)
│   ├── .gitkeep
│   ├── .htaccess               ← Deny all + disable PHP engine
│   ├── .locks/                 ← Lock token storage (auto-created)
│   └── .logs/                  ← Request logs (auto-created)
├── config.php                  ← User configuration (gitignored)
├── config.example.php          ← Example config committed to repo
├── Dockerfile                  ← Docker image definition
├── docker-compose.yml          ← One-command deployment
├── nginx.conf.example          ← Nginx config snippet
├── README.md
└── LICENSE
```

---

## 3. Configuration (`config.php`)

```php
<?php
return [
    'auth' => [
        'username' => 'safwan',
        'password' => 'abczmnm',
    ],
    'storage_path'  => __DIR__ . '/data/',
    'max_upload_size' => 104857600,  // 100MB — must stay in sync with php.ini upload_max_filesize + post_max_size
    'lock_dir'      => __DIR__ . '/data/.locks/',
    'lock_timeout'  => 600,          // Maps to WebDAV Timeout header format: Second-600
    'hide_dotfiles' => true,         // Affects both UI rendering AND PROPFIND response filtering
    'log_level'     => 'info',       // 'debug' | 'info' | 'error' | 'none'
    'log_dir'       => __DIR__ . '/data/.logs/',
    'max_log_size'  => 10485760,     // 10MB — auto-rotate when exceeded
    'depth_infinity_cap' => 1000,    // Max entries when Depth: infinity is requested
];
```

`config.example.php` is committed to the repo with placeholder values. Users copy it to `config.php` and customize. `config.php` is listed in `.gitignore`.

---

## 4. Routing Logic (`public/index.php`)

```
Request arrives
  → Load config.php
  → .htaccess rewrites everything to index.php
  → ignore_user_abort(true) — prevent partial file cleanup issues
  → Determine request type:
      1. Browser UI? (GET AND Accept: text/html AND User-Agent contains "Mozilla")
          → Render management dashboard (lib/ui.php)
      2. Everything else → treat as WebDAV
          → Authenticate (lib/auth.php — Basic Auth, 401 if failed)
          → Log incoming request (lib/log.php)
          → Route by HTTP method to lib/dav.php handlers:
              OPTIONS  → handleOptions()
              PROPFIND → handlePropFind()
              PROPPATCH→ handlePropPatch()   ← dummy 207 response
              GET      → handleGet()         ← with Range support (lib/range.php)
              HEAD     → handleHead()
              PUT      → handlePut()         ← stream-based, atomic via temp file
              DELETE   → handleDelete()      ← recursive for collections
              MKCOL    → handleMkCol()
              MOVE     → handleMove()
              COPY     → handleCopy()
              LOCK     → handleLock()        ← via lib/lock.php
              UNLOCK   → handleUnlock()      ← via lib/lock.php
              default  → 405 Method Not Allowed
          → Log response status (lib/log.php)
```

---

## 5. Authentication (`lib/auth.php`)

```php
function require_auth($config): void {
    // 1. Send 401 with WWW-Authenticate: Basic realm="saf1.me WebDAV"
    // 2. Validate credentials against config['auth']
    // 3. Abort with 401 on failure
}
```

---

## 6. Shared Utilities (`lib/util.php`)

Centralized helpers to avoid duplication across handlers:

| Function                                                    | Purpose                                                                              |
| ----------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| `resolve_path(string $uri, string $storage): string\|false` | Path traversal protection via `realpath()` — returns `false` if path escapes storage |
| `is_macos_finder(): bool`                                   | User-Agent contains `Darwin` or `WebDAVFS`                                           |
| `is_windows_miniredir(): bool`                              | User-Agent contains `Microsoft-WebDAV-MiniRedir`                                     |
| `is_gvfs(): bool`                                           | User-Agent contains `GVFS`                                                           |
| `xml_escape(string $str): string`                           | `htmlspecialchars($str, ENT_XML1, 'UTF-8')` wrapper                                  |
| `load_config(): array`                                      | Load + validate `config.php`, error if missing required keys                         |

---

## 7. WebDAV Protocol Engine (`lib/dav.php`)

### 7.1 OPTIONS Response

```
HTTP/1.1 200 OK
DAV: 1, 2
Allow: OPTIONS, GET, HEAD, PUT, DELETE, MKCOL, COPY, MOVE, PROPFIND, PROPPATCH, LOCK, UNLOCK
MS-Author-Via: DAV
Accept-Ranges: bytes
Content-Length: 0
```

**`MS-Author-Via: DAV`** — Windows MiniRedir uses this to confirm DAV authoring support. Without it, some Windows clients fall back to read-only mode.

**`Accept-Ranges: bytes`** — Signals partial content support (see §7.5).

### 7.2 Supported Methods

| Method      | Purpose                      | Notes                                                                  |
| ----------- | ---------------------------- | ---------------------------------------------------------------------- |
| `OPTIONS`   | Announce DAV compliance      | Return headers above                                                   |
| `PROPFIND`  | Directory listing + metadata | Most complex handler — see §7.3                                        |
| `PROPPATCH` | Set/change properties        | **Dummy handler** — returns 207 success, stores nothing                |
| `GET`       | Download file                | **Stream-based** + Range support. MUST send `Content-Length`. See §7.5 |
| `HEAD`      | Metadata only                | Same as GET but no body                                                |
| `PUT`       | Upload/create file           | **Stream-based** + atomic temp file + byte counter. See §7.6           |
| `DELETE`    | Delete resource              | **Recursive** for collections. See §7.7                                |
| `MKCOL`     | Create directory             | 201 on success, 405 if exists, 409 if parent missing                   |
| `MOVE`      | Move/rename                  | Parse `Destination` + `Overwrite` headers                              |
| `COPY`      | Copy resource                | Recursive for directories                                              |
| `LOCK`      | Acquire write lock           | Via `lib/lock.php`. See §7.8                                           |
| `UNLOCK`    | Release write lock           | Via `lib/lock.php`. See §7.8                                           |

### 7.3 PROPFIND — The Critical Handler

**Request parsing:**

- Read `Depth` header: `0`, `1`, or `infinity`
- Parse XML body for `<D:allprop/>`, `<D:propname/>`, or `<D:prop>` with specific props
- Default to `allprop` if body is empty (Windows sometimes sends empty bodies)
- If `Depth: infinity` → cap at `depth_infinity_cap` entries (configurable, default 1000) and treat as `Depth: 1` for the overflow. Documented simplification matching KaraDAV's approach.

**XML generation:** Use PHP's `XMLWriter` (core extension, zero dependencies) for automatic escaping. Filenames containing `&`, `<`, or `>` will produce valid XML without manual `htmlspecialchars()` calls:

```php
$xml = new XMLWriter();
$xml->openMemory();
$xml->startDocument('1.0', 'utf-8');
$xml->startElementNS('D', 'multistatus', 'DAV:');
// Per-resource elements with automatic escaping
$xml->endElement();
echo $xml->outputMemory();
```

**Response — 207 Multi-Status:**

For each resource:

```xml
<?xml version="1.0" encoding="utf-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:href>/path/to/resource</D:href>        <!-- URL-encoded, trailing / for collections -->
    <D:propstat>
      <D:prop>
        <D:resourcetype><D:collection/></D:resourcetype>
        <D:displayname>resource name</D:displayname>
        <D:getcontentlength>12345</D:getcontentlength>
        <D:getlastmodified>RFC 2616 date</D:getlastmodified>
        <D:creationdate>ISO 8601 date</D:creationdate>
        <D:getcontenttype>application/octet-stream</D:getcontenttype>
        <D:getetag>"mtime-size"</D:getetag>
        <D:supportedlock>
          <D:lockentry>
            <D:lockscope><D:exclusive/></D:lockscope>
            <D:locktype><D:write/></D:locktype>
          </D:lockentry>
        </D:supportedlock>
        <D:lockdiscovery/>
      </D:prop>
      <D:status>HTTP/1.1 200 OK</D:status>
    </D:propstat>
  </D:response>
</D:multistatus>
```

**ETag generation — no inode:**

```php
// OLD (v2): md5($inode . $mtime . $size) — leaks filesystem metadata, breaks on restart
// NEW (v3): sprintf('"%x-%x"', $mtime, $size) — matches Nginx/lighttpd format
$etag = sprintf('"%x-%x"', filemtime($filepath), filesize($filepath));
```

**macOS Finder quota fix (`lib/compat.php`):**
When `is_macos_finder()` returns true, **omit all quota-related properties** (`quota-available-bytes`, `quota-used-bytes`, `quotaused`) from the PROPFIND response. Do not return them as empty elements or zero values — exclude them entirely. macOS Finder hangs for ~90 seconds waiting for a response if these are present. Also filter out `.part.*` temporary upload files from listings.

**Property filtering (`lib/compat.php`):**
If `hide_dotfiles` is true in config, filter out files starting with `.` from both the UI rendering AND PROPFIND responses. This hides `.DS_Store`, `._*` resource forks, `.locks/`, and `.logs/`.

**Client-specific checks in PROPFIND:**

- `href` MUST be URL-encoded (paths with spaces break Windows)
- Collections MUST have trailing `/` in `href`
- `getlastmodified` MUST be RFC 2616: `Tue, 15 Nov 1994 08:12:31 GMT`
- Root collection (`/`) MUST be first response when Depth >= 1
- Always include `supportedlock` — Windows decides whether to attempt LOCK based on this

### 7.4 PROPPATCH — Dummy Handler

Windows File Explorer sends PROPPATCH to update file attributes (modified time, read-only flag). Returning 405 causes "File in use" or "Network Error" dialogs.

Return a valid 207 Multi-Status confirming all requested property changes succeeded, without modifying anything:

```xml
<?xml version="1.0" encoding="utf-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:href>/path/to/resource</D:href>
    <D:propstat>
      <D:prop>
        <!-- Echo back whatever props the client tried to set -->
      </D:prop>
      <D:status>HTTP/1.1 200 OK</D:status>
    </D:propstat>
  </D:response>
</D:multistatus>
```

### 7.5 GET — Streaming Download with Range Support

**Core streaming (zero memory footprint):**

```php
$fh = fopen($filepath, 'rb');
while (!feof($fh)) {
    echo fread($fh, 8192);
    flush();  // Prevent output buffering under mod_php or FPM
}
fclose($fh);
```

**Required headers:**

```php
header('Content-Type: ' . $mime);
header('Content-Length: ' . $filesize);          // REQUIRED — Windows refuses to copy without it
header('Accept-Ranges: bytes');                  // Signal range support
header('ETag: "' . sprintf('%x-%x', $mtime, $size) . '"');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('X-Content-Type-Options: nosniff');       // Prevent MIME sniffing
```

**Range request handling (`lib/range.php`):**

When the `Range: bytes=start-end` header is present:

1. Parse `bytes=start-end` syntax
2. Validate range against file size
3. Return `206 Partial Content` with:
   ```
   Content-Range: bytes start-end/total
   Content-Length: (end - start + 1)
   ```
4. Stream only the requested byte range using `fseek()` + chunked `fread()`
5. If range is unsatisfiable (start > filesize) → `416 Range Not Satisfiable`
6. If no Range header → `200 OK` with full file (current behavior)

This enables resumable downloads, video seeking in browsers, and bandwidth reduction.

### 7.6 PUT — Streaming Upload (Atomic + Enforced Limits)

**Atomic write via temporary file:**

```php
$tmpfile = $filepath . '.part.' . uniqid('', true);
$in  = fopen('php://input', 'rb');
$out = fopen($tmpfile, 'wb');

// Streaming byte counter — handles chunked encoding where Content-Length may be absent
$max = $config['max_upload_size'];
$written = 0;
while (!feof($in)) {
    $chunk = fread($in, 8192);
    $written += strlen($chunk);
    if ($written > $max) {
        fclose($in); fclose($out);
        unlink($tmpfile);
        http_response_code(507);  // Insufficient Storage
        return;
    }
    fwrite($out, $chunk);
}
fclose($in);
fclose($out);

rename($tmpfile, $filepath);  // Atomic on same filesystem
http_response_code(file_exists($filepath) ? 204 : 201);
```

**Connection abort cleanup:**

```php
// At top of index.php:
ignore_user_abort(true);

// After PUT:
register_shutdown_function(function() use ($tmpfile) {
    if (connection_aborted() && file_exists($tmpfile)) {
        unlink($tmpfile);  // Remove incomplete upload
    }
});
```

The `.part.*` suffix is hidden from PROPFIND responses by `lib/compat.php`.

### 7.7 DELETE — Recursive for Collections

**RFC 4918 §9.6.1 requires** that DELETE on a collection remove the collection and **all descendants**, not just empty directories:

```php
function recursive_delete(string $path): void {
    if (is_file($path)) {
        unlink($path);
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
    }
    rmdir($path);
}
```

**Lock enforcement:** Before deleting, check if any descendant is locked. If locked AND request lacks valid `If` header with matching token → return `423 Locked`. For a single-user server, rejecting the entire DELETE is acceptable (no need for 207 Multi-Status with per-resource errors).

### 7.8 LOCK / UNLOCK (`lib/lock.php`)

**Lock storage:** JSON files in `{storage_path}/.locks/`

**LOCK handler:**

1. Parse XML body for `<D:lockscope>`, `<D:locktype>`, `<D:owner>`
2. Check if resource is already locked by another token → `423 Locked`
3. Generate UUID v4 lock token: `opaquelocktoken:` + UUID
4. Store lock metadata as JSON in `.locks/`
5. Return `200 OK` with `lockdiscovery` XML body and `Timeout: Second-600` header

**UNLOCK handler:**

1. Read `Lock-Token` header
2. Delete corresponding `.locks/*.lock` file
3. Return `204 No Content`

**Lock refresh:** LOCK on already-locked resource with `If` header containing existing token → extend timeout, return updated `lockdiscovery`.

**Garbage collection:** On every `PROPFIND`, `OPTIONS`, or `LOCK` call, scan `.locks/` directory and delete any lock file where `expires < time()`. Prevents dead locks after client crashes. Takes < 1ms for typical counts.

**Enforcement:** Before `PUT`, `DELETE`, `MOVE`, `COPY` — check if resource is locked. If locked AND request doesn't include valid `If` header → `423 Locked`.

---

## 8. Error Handler (`lib/error.php`)

WebDAV errors should return XML bodies with `<D:error>` elements:

```php
function dav_error(int $status, string $condition = ''): void {
    http_response_code($status);
    header('Content-Type: application/xml; charset=utf-8');
    $xml = '<?xml version="1.0" encoding="utf-8"?>';
    $xml .= '<D:error xmlns:D="DAV:">';
    if ($condition) $xml .= "<D:{$condition}/>";
    $xml .= '</D:error>';
    echo $xml;
}
```

Replaces all inline `http_response_code()` + bare `return` patterns in `dav.php`.

---

## 9. Logger (`lib/log.php`)

```php
function log_request(string $method, string $path, int $status, ?string $error = null): void {
    $entry = sprintf("[%s] %s %s → %d%s\n",
        gmdate('Y-m-d H:i:s'), $method, $path, $status,
        $error ? " | {$error}" : ''
    );
    file_put_contents(LOG_DIR . '/webdav.log', $entry, FILE_APPEND | LOCK_EX);
    // Auto-rotate: if filesize > config['max_log_size'], rename to webdav.log.1
}
```

Logs are stored in `data/.logs/` — outside web root, protected by `.htaccess`.

---

## 10. MIME Type Detection (`lib/mime.php`)

```php
function detect_mime(string $filepath): string {
    $mime = function_exists('mime_content_type') ? mime_content_type($filepath) : false;
    if ($mime && $mime !== 'application/octet-stream') return $mime;

    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $map = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'png'  => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp',
        'mp4'  => 'video/mp4', 'webm' => 'video/webm', 'mkv' => 'video/x-matroska',
        'mp3'  => 'audio/mpeg', 'wav' => 'audio/wav', 'flac' => 'audio/flac',
        'zip'  => 'application/zip', 'rar' => 'application/vnd.rar',
        '7z'   => 'application/x-7z-compressed', 'tar' => 'application/x-tar',
        'gz'   => 'application/gzip',
        'txt'  => 'text/plain', 'html' => 'text/html', 'css' => 'text/css',
        'js'   => 'application/javascript', 'json' => 'application/json',
        'xml'  => 'application/xml', 'csv' => 'text/csv', 'md' => 'text/markdown',
    ];
    return $map[$ext] ?? 'application/octet-stream';
}
```

---

## 11. Management UI (`lib/ui.php`)

### 11.1 Design Direction

**Aesthetic:** Brutalist terminal — dark, monospaced, amber accents.

| Element        | Choice                            |
| -------------- | --------------------------------- |
| **Font**       | JetBrains Mono (Google Fonts CDN) |
| **Background** | `#0a0a0a`                         |
| **Surface**    | `#141414`                         |
| **Text**       | `#e8e4dc`                         |
| **Muted**      | `#5a5550`                         |
| **Accent**     | `#e8a530` (amber)                 |
| **Error**      | `#e85050`                         |
| **Borders**    | `1px solid #2a2a2a`               |

### 11.2 Layout

```
┌─────────────────────────────────────────────────────┐
│  s3.saf1.me                              [user: safwan]
├─────────────────────────────────────────────────────┤
│  / > documents > projects                           │  ← breadcrumb
├──────────┬──────────────────────────────────────────┤
│          │  Name         Size     Modified           │
│ UPLOAD   │  ─────────────────────────────────────── │
│ [drag &  │  📁 images/    —       Jun 01, 2026      │
│  drop    │  📁 notes/     —       May 28, 2026      │
│  zone]   │  📄 readme.md  2.4 KB  Jun 03, 2026      │
│          │  📄 data.csv   145 KB  Jun 02, 2026      │
│ NEW FOLDER│                                        │
│ [+ btn]  │                                         │
│          │                                         │
│ ──────── │                                         │
│ Stats:   │                                         │
│ 12 files │                                         │
│ 3.2 MB   │                                         │
└──────────┴──────────────────────────────────────────┘
```

### 11.3 Features

| Feature           | Implementation                                           |
| ----------------- | -------------------------------------------------------- |
| File listing      | PHP `scandir()` + `stat()`, filter dotfiles per config   |
| Breadcrumb nav    | Parse URL path into clickable segments                   |
| Upload            | Drag-and-drop + file picker, `fetch()` + `FormData` POST |
| Upload size check | JS checks `file.size` against config max before upload   |
| New folder        | JS modal → POST to `?action=mkdir`                       |
| Delete            | Confirmation dialog → POST to `?action=delete`           |
| Rename            | Inline input → POST to `?action=rename`                  |
| Download          | Direct `<a>` link to `?action=download&path=...`         |
| File info         | Size (human-readable), MIME type, last modified          |
| Usage stats       | Total files count, total size                            |
| Dotfiles hidden   | Per config `hide_dotfiles` setting                       |
| Responsive        | Single-column mobile, two-column desktop                 |

### 11.4 UI Actions

```
GET  /                         → render dashboard (current directory)
GET  /?action=download&path=X → stream file download
POST /?action=upload           → handle multipart upload
POST /?action=mkdir            → create directory
POST /?action=delete           → delete file/directory
POST /?action=rename           → rename file/directory
```

### 11.5 JavaScript

- Vanilla JS only, no framework
- `fetch()` for async operations
- Dynamic DOM updates (no full page reload)
- Drag-and-drop via `dragover`/`dragleave`/`drop` events
- Confirmation dialogs for destructive actions
- Loading states during async operations

---

## 12. `.htaccess` (`public/.htaccess`)

```apache
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Route everything to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Disable directory listing
Options -Indexes

# Correct charset
AddDefaultCharset UTF-8

# Security headers
Header always set X-Content-Type-Options "nosniff"
```

### Data Directory Protection (`data/.htaccess`)

```apache
Deny from all

<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$">
    SetHandler None
    ForceType text/plain
</FilesMatch>

# Disable PHP engine entirely in this directory
php_flag engine off
```

---

## 13. Nginx Configuration (`nginx.conf.example`)

```nginx
server {
    listen 443 ssl http2;
    server_name s3.saf1.me;

    ssl_certificate     /etc/letsencrypt/live/s3.saf1.me/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/s3.saf1.me/privkey.pem;

    root /var/www/saf1.me/public;
    index index.php;

    # Upload limits
    client_max_body_size 100M;
    client_body_buffer_size 16k;
    client_body_temp_path /var/cache/nginx/client_temp;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # Pass WebDAV headers to PHP
        fastcgi_param HTTP_DEPTH $http_depth;
        fastcgi_param HTTP_DESTINATION $http_destination;
        fastcgi_param HTTP_IF $http_if;
        fastcgi_param HTTP_LOCK_TOKEN $http_lock_token;
        fastcgi_param HTTP_OVERWRITE $http_overwrite;
        fastcgi_param HTTP_IF_NONE_MATCH $http_if_none_match;
        fastcgi_param HTTP_RANGE $http_range;
    }

    # Block direct access to data directory
    location /data/ {
        deny all;
        return 404;
    }
}
```

---

## 14. Docker Support

### `Dockerfile`

```dockerfile
FROM php:8.2-apache

RUN a2enmod rewrite headers

# Install required PHP extensions
RUN apt-get update && apt-get install -y libxml2-dev && \
    docker-php-ext-install fileinfo xmlwriter && \
    rm -rf /var/lib/apt/lists/*

COPY public/ /var/www/html/
COPY lib/ /var/www/lib/
COPY config.example.php /var/www/config.example.php

# Config is mounted as volume — never baked into image
RUN mkdir -p /var/www/data/.locks /var/www/data/.logs

VOLUME ["/var/www/data", "/var/www/config"]

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -sf http://localhost/ -o /dev/null || exit 1

EXPOSE 80
```

### `docker-compose.yml`

```yaml
version: "3.8"
services:
  webdav:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./data:/var/www/data
      - ./config.php:/var/www/config.php
      - ./logs:/var/www/data/.logs
    restart: unless-stopped
```

---

## 15. Security Checklist

| Measure                               | Implementation                                                                                    |
| ------------------------------------- | ------------------------------------------------------------------------------------------------- |
| **HTTPS mandatory**                   | `.htaccess` 301 redirect + Nginx SSL                                                              |
| **Path traversal blocked**            | `realpath()` validation via `lib/util.php` — every request                                        |
| **Data outside web root**             | `data/` adjacent to `public/`, not inside it                                                      |
| **PHP execution in data dir blocked** | `data/.htaccess`: `Deny from all` + `php_flag engine off` + Nginx `location /data/ { deny all; }` |
| **Credentials not in repo**           | `config.php` in `.gitignore`, `config.example.php` committed                                      |
| **No directory listing**              | `Options -Indexes`                                                                                |
| **Upload size enforced**              | Server-side streaming byte counter + client-side JS pre-check                                     |
| **MIME sniffing prevented**           | `X-Content-Type-Options: nosniff` on all responses                                                |
| **Lock token unguessable**            | UUID v4 (128-bit random)                                                                          |
| **ETag safe**                         | No inode — uses `mtime + size` only                                                               |
| **Symlink policy**                    | `realpath()` resolves symlinks; those escaping `data/` are blocked by traversal check             |
| **Connection abort cleanup**          | `ignore_user_abort(true)` + shutdown handler removes partial uploads                              |

---

## 16. Protocol Edge Cases & Client Quirks

| #   | Issue                            | Client       | Solution                                              | Module       |
| --- | -------------------------------- | ------------ | ----------------------------------------------------- | ------------ |
| 1   | PROPFIND with empty body         | Windows      | Default to `<allprop/>`                               | `dav.php`    |
| 2   | PROPPATCH for file attributes    | Windows      | Dummy 207 success response                            | `dav.php`    |
| 3   | Missing Content-Length on GET    | Windows      | Always send `filesize()` header                       | `dav.php`    |
| 4   | LOCK before every write          | Windows      | Implement LOCK/UNLOCK + `supportedlock`               | `lock.php`   |
| 5   | `MS-Author-Via` required         | Windows      | Return `MS-Author-Via: DAV` in OPTIONS                | `dav.php`    |
| 6   | `.DS_Store` and `._*` spam       | macOS        | Hide in UI + PROPFIND when `hide_dotfiles` is true    | `compat.php` |
| 7   | Quota property hang (~90s)       | macOS        | **Omit all quota properties** when UA matches Finder  | `compat.php` |
| 8   | Trailing slash on collections    | Both         | Always include in `href`, normalize incoming URIs     | `util.php`   |
| 9   | `Transfer-Encoding: chunked` PUT | Windows      | PHP handles via `php://input` stream                  | `dav.php`    |
| 10  | URL-encoded paths with spaces    | Both         | `urlencode()` all `href` values in PROPFIND           | `dav.php`    |
| 11  | Depth: infinity on large dirs    | macOS/Linux  | Cap at 1000 entries, fallback to Depth: 1             | `compat.php` |
| 12  | `If` header with lock tokens     | Windows      | Parse and validate lock tokens                        | `lock.php`   |
| 13  | Renaming to existing target      | Windows MOVE | Honor `Overwrite: T` / `Overwrite: F` header          | `dav.php`    |
| 14  | Recursive DELETE required        | Both         | Recursive directory traversal in DELETE               | `dav.php`    |
| 15  | Partial upload on disconnect     | Both         | Temp file + atomic rename + cleanup on abort          | `dav.php`    |
| 16  | Range requests for video/seek    | Browsers     | Parse `Range` header, return 206 with `Content-Range` | `range.php`  |
| 17  | Filenames with `&`, `<`, `>`     | Both         | `XMLWriter` for automatic escaping                    | `dav.php`    |
| 18  | `.part.*` temp files visible     | Both         | Hidden from PROPFIND responses                        | `compat.php` |

---

## 17. Implementation Phases

| Phase | Task                                                                               | Est. Lines | Files                                                      | Edge Cases Covered |
| ----- | ---------------------------------------------------------------------------------- | ---------- | ---------------------------------------------------------- | ------------------ |
| 1     | Config system + example + `.gitignore`                                             | ~30        | `config.example.php`, `.gitignore`                         | —                  |
| 2     | Entry point + routing + auth                                                       | ~60        | `public/index.php`, `lib/auth.php`                         | —                  |
| 3     | Utilities: path resolution, client detection, config loader                        | ~50        | `lib/util.php`                                             | #8, #11            |
| 4     | Error handler + Logger                                                             | ~70        | `lib/error.php`, `lib/log.php`                             | —                  |
| 5     | MIME type detection                                                                | ~70        | `lib/mime.php`                                             | —                  |
| 6     | DAV core: OPTIONS, PROPFIND (Depth 0/1, XMLWriter)                                 | ~220       | `lib/dav.php`                                              | #1, #7, #10, #17   |
| 7     | DAV streaming: GET, HEAD (Range support)                                           | ~120       | `lib/dav.php`, `lib/range.php`                             | #3, #16            |
| 8     | DAV streaming: PUT (atomic temp file + byte counter + abort cleanup)               | ~80        | `lib/dav.php`                                              | #9, #15            |
| 9     | DAV writes: DELETE (recursive), MKCOL                                              | ~80        | `lib/dav.php`                                              | #14                |
| 10    | DAV moves: MOVE, COPY (recursive)                                                  | ~90        | `lib/dav.php`                                              | #13                |
| 11    | DAV dummy: PROPPATCH                                                               | ~40        | `lib/dav.php`                                              | #2                 |
| 12    | Compatibility layer: macOS quota skip, dotfile filter, Depth cap, temp file hiding | ~40        | `lib/compat.php`                                           | #6, #7, #11, #18   |
| 13    | Lock system: LOCK, UNLOCK, garbage collection, enforcement                         | ~100       | `lib/lock.php`                                             | #4, #5, #12        |
| 14    | Security headers + path traversal + symlink policy                                 | ~30        | `lib/dav.php`, `public/index.php`                          | —                  |
| 15    | `.htaccess` files (public + data) + Nginx config                                   | ~40        | `public/.htaccess`, `data/.htaccess`, `nginx.conf.example` | —                  |
| 16    | Management UI: HTML structure + CSS                                                | ~200       | `lib/ui.php`                                               | —                  |
| 17    | Management UI: JS (upload, delete, rename, drag-drop)                              | ~150       | `lib/ui.php`                                               | —                  |
| 18    | Docker + docker-compose + health check                                             | ~50        | `Dockerfile`, `docker-compose.yml`                         | —                  |
| 19    | README.md                                                                          | ~120       | `README.md`                                                | —                  |
| 20    | Cross-client testing + fixes                                                       | —          | —                                                          | —                  |

**Total estimated:** ~1,600 lines across all files. Zero external dependencies.

---

## 18. Testing Checklist

### Automated (run via `curl` or PHP CLI script)

| Test                  | Command                                           | Expected                      |
| --------------------- | ------------------------------------------------- | ----------------------------- |
| Path traversal        | `PROPFIND /../../../../etc/passwd`                | 403                           |
| Upload size limit     | PUT with Content-Length > max                     | 507                           |
| PROPFIND XML validity | Parse response with `simplexml_load_string()`     | No errors                     |
| ETag consistency      | GET a file twice, compare ETags                   | Match                         |
| Lock token validation | PUT without `If` on locked resource               | 423                           |
| Range request         | GET with `Range: bytes=0-9`                       | 206 + correct `Content-Range` |
| Recursive DELETE      | MKCOL dir + PUT file + DELETE dir                 | 204, dir gone                 |
| OPTIONS headers       | Check for `DAV`, `MS-Author-Via`, `Accept-Ranges` | All present                   |

### Manual — Windows File Explorer

- [ ] Map network drive to `https://s3.saf1.me` → prompts for credentials
- [ ] Browse directories → files and folders visible
- [ ] Copy file TO drive → uploads
- [ ] Copy file FROM drive → downloads (Content-Length correct)
- [ ] Create new folder → appears
- [ ] Rename file → works
- [ ] Delete file → removed, no "file in use" error
- [ ] Delete folder with contents → recursive delete works
- [ ] Drag-drop large file (50MB+) → completes
- [ ] Edit text file in Notepad → saves back correctly
- [ ] No 90-second hangs on any operation

### Manual — Linux (Nautilus / GVFS)

- [ ] `gio mount dav://s3.saf1.me` → prompts for credentials
- [ ] Browse, upload, download, delete, rename all work
- [ ] `davfs2` mount via `/etc/fstab` → read/write

### Manual — macOS Finder

- [ ] Connect to server → mounts without 90-second delay
- [ ] Browse, upload, download, delete, rename
- [ ] No `.DS_Store` errors in logs

### Manual — Management UI

- [ ] Open `https://s3.saf1.me` in browser → dashboard loads
- [ ] Navigate directories via breadcrumb
- [ ] Upload via drag-and-drop and file picker
- [ ] Create, delete, rename folders and files
- [ ] Download file
- [ ] Stats display correctly
- [ ] Dotfiles hidden
- [ ] Mobile layout usable
