# LeanDAV - Lightweight PHP WebDAV Server with Web UI

[![PHP Version](https://img.shields.io/badge/PHP-8.0+-777bb4.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Docker Support](https://img.shields.io/badge/Docker-Supported-2496ED.svg)](https://www.docker.com/)

**LeanDAV** is an ultra-lightweight, zero-dependency PHP WebDAV server and self-hosted file manager. It allows you to instantly turn any PHP/Apache server or Docker environment into a private cloud storage drive. 

Mount your files natively on Windows File Explorer, macOS Finder, or Linux—or manage them from anywhere using the beautifully designed, responsive browser UI.

> **Why LeanDAV?** Most WebDAV solutions (like Nextcloud or full SabreDAV implementations) are heavy, require complex database setups, and use massive amounts of memory. LeanDAV is designed to be a "drop-in" solution. No databases, no Composer dependencies—just fast, secure file serving.

![LeanDAV Web UI Screenshot](link-to-your-screenshot-image.png)
*(Replace the link above with a relative path to the screenshot you showed me earlier, e.g., `./docs/screenshot.png`)*

## Key Features

- **Zero Dependencies:** Pure PHP core implementation. No Composer, no databases required.
- **Cross-Platform Native Mounting:** Fully tested and compatible with Windows File Explorer, macOS Finder, and Linux (Nautilus/GVFS/davfs2).
- **Modern Web Dashboard:** Includes a sleek, dark-mode browser interface for uploading, downloading, and file management.
- **Docker Ready:** Deploy in seconds using the provided Docker Compose configurations.
- **Security First:** Built-in protection against path traversal, strict HTTPS enforcement, and secure HTTP headers.
- **Smart File Handling:** Supports atomic stream-based uploads, resumable downloads (Range Requests), and video seeking.
- **Robust Locking:** File-based lock management (Class 1+2 protocol support) with automatic garbage collection to prevent "File in use" errors.

---

## Quick Start Installation

You can run LeanDAV on a traditional web server or via Docker. 

### Option A: Docker Deployment (Recommended)

The fastest way to get your self-hosted WebDAV server running:

```bash
# Clone the repository
git clone https://github.com/lubdhak7414/LeanDAV.git
cd LeanDAV

# Create configuration
cp config.example.php config.php
# Edit config.php to set your custom username and password

# Build and start the container
docker compose up -d
```
Your WebDAV server and UI are now accessible at `http://localhost:8080`. 

*(Note: For production, we recommend using the provided `docker-compose.prod.yml` which includes automatic HTTPS via Caddy).*

### Option B: Manual PHP/Apache Setup

**Requirements:** PHP 8.0+ (with `fileinfo`, `xmlwriter`, `dom`, `json` extensions), Apache (with `mod_rewrite`) or Nginx.

1. Clone the repository to your web root.
2. `cp config.example.php config.php` and configure your credentials.
3. Apply the necessary write permissions to the data directories:
   ```bash
   chmod 755 data/ data/.locks/ data/.logs/
   ```
4. Ensure Apache modules are active: `sudo a2enmod rewrite headers && sudo systemctl restart apache2`
5. Navigate to your domain in a browser to view the UI, or connect via your OS file manager.

---

## ⚙️ Configuration (`config.php`)

LeanDAV is highly customizable. Edit `config.php` to adjust storage paths, upload limits, and security settings:

```php
<?php
return [
    'auth' => [
        'username' => 'admin',       // Change this!
        'password' => 'supersecret', // Change this!
    ],
    'storage_path' => __DIR__ . '/data/',
    'max_upload_size' => 104857600,  // 100MB limit
    'lock_dir' => __DIR__ . '/data/.locks/',
    'lock_timeout' => 600,
    'hide_dotfiles' => true,         // Hides .htaccess, .DS_Store, etc.
    'log_level' => 'info',
];
```

---

## How to Mount as a Network Drive

Once LeanDAV is running (ideally over HTTPS), you can mount it directly to your operating system.

### Windows
1. Open **File Explorer**.
2. Right-click **This PC** → **Map network drive**.
3. Enter your server URL: `https://your-domain.com`
4. Check **"Connect using different credentials"** and enter your `config.php` login.
*(Alternative Windows methods available in [windows-alternative.md](windows-alternative.md))*

### macOS
1. Open **Finder**.
2. Press `Cmd + K` (or Go → Connect to Server).
3. Enter `https://your-domain.com` and provide your credentials.

### Linux
**GNOME Desktop:**
```bash
gio mount dav://your-domain.com
```
**Command Line (davfs2):**
Add to `/etc/fstab`: `https://your-domain.com /mnt/webdav davfs user,noauto 0 0`
Then run: `sudo mount /mnt/webdav`

---

## 🛠️ API & Under the Hood

LeanDAV strictly adheres to **RFC 4918**. 

### Supported WebDAV Methods
| Method | Description |
|--------|-------------|
| `OPTIONS`, `PROPFIND` | Directory listing, metadata discovery, and DAV compliance |
| `GET`, `HEAD` | Download files (with byte-range support) |
| `PUT`, `MKCOL` | Atomic file uploads and directory creation |
| `DELETE`, `MOVE`, `COPY` | Recursive file/folder operations |
| `LOCK`, `UNLOCK` | Write-lock acquisition for safe simultaneous editing |

### Browser UI Endpoints
The frontend dashboard interacts with LeanDAV via standard HTTP POST/GET requests for maximum compatibility:
- `/?action=upload` (POST)
- `/?action=download&path=X` (GET)
- `/?action=mkdir`, `delete`, `rename` (POST)

---

## Troubleshooting

*   **90-second delay on macOS?** LeanDAV automatically omits quota properties for Finder clients to fix this known Apple bug. Ensure you are on the latest version.
*   **Windows "File in use" error?** Your client is failing to send proper lock tokens. LeanDAV supports robust LOCK/UNLOCK to prevent this. Ensure your Windows WebClient service is running properly.
*   **Large Uploads Failing?** Check your server's `php.ini` configuration. Ensure `upload_max_filesize`, `post_max_size`, and `max_execution_time` are set high enough.

## License

This project is open-source software licensed under the [MIT License](LICENSE).
