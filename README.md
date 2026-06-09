# LeanDAV (WebDAV) Server

A zero-dependency PHP application implementing the WebDAV protocol (RFC 4918) for native mounting on Windows File Explorer, Linux file managers, and macOS Finder — plus a browser-based management dashboard.

## Features

- **Full WebDAV Protocol Support** — Class 1+2 implementation with read/write and locking
- **Zero Dependencies** — Only PHP core extensions (fileinfo, xmlwriter, dom, json)
- **Cross-Client Compatible** — Works with Windows File Explorer, macOS Finder, Linux Nautilus/GVFS
- **Browser Dashboard** — Modern management UI for file operations
- **Atomic Uploads** — Stream-based with temporary files and connection abort cleanup
- **Range Requests** — Supports resumable downloads and video seeking
- **Lock Management** — File-based locking with automatic garbage collection
- **Security First** — Path traversal protection, HTTPS enforcement, security headers

## Requirements

- PHP 8.0+ with extensions: fileinfo, xmlwriter, dom, json
- Apache with mod_rewrite or Nginx
- HTTPS (Basic Auth sends credentials in base64)

## Quick Start

### 1. Clone and Configure

```bash
git clone https://github.com/lubdhak7414/LeanDAV/
cd LeanDAV
cp config.example.php config.php
# Edit config.php with your credentials and settings
```

### 2. Set Permissions

```bash
chmod 755 data/
chmod 755 data/.locks/
chmod 755 data/.logs/
```

### 3. Configure Web Server

#### Apache

Ensure `mod_rewrite` and `mod_headers` are enabled:

```bash
sudo a2enmod rewrite headers
sudo systemctl restart apache2
```

#### Nginx

Copy `nginx.conf.example` to your Nginx configuration directory and adjust paths.

### 4. Test

Open `https://your-domain.com` in a browser to access the management dashboard.

## Configuration

Edit `config.php` to customize:

```php
<?php
return [
    'auth' => [
        'username' => 'your-username',
        'password' => 'your-password',
    ],
    'storage_path' => __DIR__ . '/data/',
    'max_upload_size' => 104857600,  // 100MB
    'lock_dir' => __DIR__ . '/data/.locks/',
    'lock_timeout' => 600,
    'hide_dotfiles' => true,
    'log_level' => 'info',
    'log_dir' => __DIR__ . '/data/.logs/',
    'max_log_size' => 10485760,
    'depth_infinity_cap' => 1000,
];
```

## Docker Deployment

```bash
# Build and start
docker-compose up -d

# Access at http://localhost:8080
```

## Mount as Network Drive

### Windows

1. Open File Explorer
2. Right-click "This PC" → "Map network drive"
3. Enter: `https://your-domain.com`
4. Check "Connect using different credentials"
5. Enter your username and password

> **Note:** For an alternative method using PowerShell or registry tweaks, see [windows-alternative.md](windows-alternative.md).

### macOS

1. Open Finder
2. Press Cmd+K or go to Go → Connect to Server
3. Enter: `https://your-domain.com`
4. Enter your credentials when prompted

### Linux (GNOME)

```bash
gio mount dav://your-domain.com
```

### Linux (davfs2)

Add to `/etc/fstab`:

```
https://your-domain.com /mnt/webdav davfs user,noauto 0 0
```

Then:

```bash
sudo mount /mnt/webdav
```

## API Reference

### WebDAV Methods

| Method | Description |
|--------|-------------|
| OPTIONS | Announce DAV compliance |
| PROPFIND | Directory listing and metadata |
| PROPPATCH | Set/change properties (dummy) |
| GET | Download file with Range support |
| HEAD | Metadata only |
| PUT | Upload/create file (atomic) |
| DELETE | Delete resource (recursive) |
| MKCOL | Create directory |
| MOVE | Move/rename resource |
| COPY | Copy resource |
| LOCK | Acquire write lock |
| UNLOCK | Release write lock |

### Management UI Actions

| Action | Method | Description |
|--------|--------|-------------|
| `/` | GET | Dashboard |
| `/?action=download&path=X` | GET | Download file |
| `/?action=upload` | POST | Upload file |
| `/?action=mkdir` | POST | Create directory |
| `/?action=delete` | POST | Delete file/directory |
| `/?action=rename` | POST | Rename file/directory |

## Security

- **HTTPS Required** — Basic Auth credentials are base64 encoded
- **Path Traversal Protected** — All paths validated with `realpath()`
- **Data Outside Web Root** — Files stored in `data/` adjacent to `public/`
- **PHP Execution Blocked** — `.htaccess` prevents script execution in data directory
- **Security Headers** — X-Content-Type-Options, X-Frame-Options, etc.

## Troubleshooting

### 90-second delay on macOS

Ensure your server doesn't return quota properties. The server automatically omits these for macOS Finder clients.

### Windows "File in use" error

The server implements LOCK/UNLOCK support. Ensure your client sends proper lock tokens.

### Upload fails

Check `php.ini` settings:

```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```

## License

MIT License
