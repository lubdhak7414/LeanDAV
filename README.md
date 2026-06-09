# LeanDAV - PHP WebDAV Server with Web UI

[![PHP Version](https://img.shields.io/badge/PHP-8.0+-777bb4.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Docker Support](https://img.shields.io/badge/Docker-Supported-2496ED.svg)](https://www.docker.com/)

**LeanDAV** is a PHP-based WebDAV server with a built-in web interface for file management.

It runs without a database or external PHP dependencies and can be deployed on a standard PHP web server or with Docker.

Files can be accessed through WebDAV clients such as Windows File Explorer, macOS Finder, and Linux desktop environments, or through the included browser-based interface.

![LeanDAV Web UI Screenshot](link-to-your-screenshot-image.png)

## Why LeanDAV?

LeanDAV is intended for users who need WebDAV storage and file management without additional services or infrastructure.

The project stores files directly on disk and does not require a database. Deployment consists of copying the application files and configuring a small number of settings.

## Features

* WebDAV server implementation in PHP
* No database required
* No Composer dependencies
* Browser-based file manager
* Docker deployment support
* File uploads and downloads
* HTTP Range request support
* File and directory operations (copy, move, rename, delete)
* WebDAV locking support
* Configurable storage and upload limits
* Compatible with Windows, macOS, and Linux WebDAV clients

---

## Installation

You can run LeanDAV on a traditional web server or with Docker.

### Option A: Docker

```bash
# Clone the repository
git clone https://github.com/lubdhak7414/LeanDAV.git
cd LeanDAV

# Create configuration
cp config.example.php config.php

# Edit config.php and set your credentials

# Start the container
docker compose up -d
```

The application will be available at:

```text
http://localhost:8080
```

For production deployments, use HTTPS and a reverse proxy. The repository includes a production Docker Compose configuration with Caddy.

### Option B: PHP Web Server

#### Requirements

* PHP 8.0+
* Apache or Nginx
* PHP extensions:

  * fileinfo
  * xmlwriter
  * dom
  * json

#### Setup

1. Clone the repository into your web root.
2. Copy the example configuration:

```bash
cp config.example.php config.php
```

3. Edit `config.php` and configure your credentials.
4. Ensure the data directories are writable:

```bash
chmod 755 data/ data/.locks/ data/.logs/
```

5. If using Apache, enable the required modules:

```bash
sudo a2enmod rewrite headers
sudo systemctl restart apache2
```

6. Open your site in a browser or connect using a WebDAV client.

---

## Configuration

Configure LeanDAV by editing `config.php`.

Example:

```php
<?php

return [
    'auth' => [
        'username' => 'admin',
        'password' => 'supersecret',
    ],

    'storage_path'   => __DIR__ . '/data/',
    'max_upload_size' => 104857600,
    'lock_dir'       => __DIR__ . '/data/.locks/',
    'lock_timeout'   => 600,

    'hide_dotfiles'  => true,
    'log_level'      => 'info',
];
```

### Configuration Options

| Option            | Description                        |
| ----------------- | ---------------------------------- |
| `username`        | Login username                     |
| `password`        | Login password                     |
| `storage_path`    | Directory used for file storage    |
| `max_upload_size` | Maximum upload size in bytes       |
| `lock_dir`        | Directory used for lock files      |
| `lock_timeout`    | Lock expiration time in seconds    |
| `hide_dotfiles`   | Hide dotfiles in the web interface |
| `log_level`       | Logging verbosity                  |

---

## Connecting a WebDAV Client

Once the server is running, it can be mounted through any WebDAV-compatible client.

### Windows

1. Open **File Explorer**.
2. Right-click **This PC** → **Map network drive**.
3. Enter your WebDAV URL:

```text
https://your-domain.com
```

4. Select **Connect using different credentials**.
5. Enter your LeanDAV username and password.

### macOS

1. Open **Finder**.
2. Press **Cmd + K**.
3. Enter:

```text
https://your-domain.com
```

4. Authenticate with your credentials.

### Linux

#### GNOME

```bash
gio mount dav://your-domain.com
```

#### davfs2

Add the following entry to `/etc/fstab`:

```text
https://your-domain.com /mnt/webdav davfs user,noauto 0 0
```

Mount the share:

```bash
sudo mount /mnt/webdav
```

---

## WebDAV Support

LeanDAV implements the following WebDAV methods and is designed to work with common desktop clients.

| Method     | Purpose                        |
| ---------- | ------------------------------ |
| `OPTIONS`  | Capability discovery           |
| `PROPFIND` | Directory listing and metadata |
| `GET`      | Download files                 |
| `HEAD`     | Metadata retrieval             |
| `PUT`      | Upload files                   |
| `MKCOL`    | Create directories             |
| `DELETE`   | Remove files and directories   |
| `MOVE`     | Move files and directories     |
| `COPY`     | Copy files and directories     |
| `LOCK`     | Acquire write locks            |
| `UNLOCK`   | Release write locks            |

### Additional Support

* HTTP Range requests
* Recursive directory operations
* Stream-based uploads
* File locking with automatic cleanup

---

## Web Interface Endpoints

The web interface uses the following endpoints:

| Endpoint                   | Method |
| -------------------------- | ------ |
| `/?action=upload`          | POST   |
| `/?action=download&path=X` | GET    |
| `/?action=mkdir`           | POST   |
| `/?action=delete`          | POST   |
| `/?action=rename`          | POST   |

---

## Directory Layout

```text
.
├── config.php
├── data/
│   ├── .locks/
│   └── .logs/
├── public/
├── docker-compose.yml
└── docker-compose.prod.yml
```

---

## Troubleshooting

### Slow Connection on macOS

Some Finder versions may perform additional WebDAV property requests during connection. If connection times are unexpectedly slow, ensure you are running a recent version of macOS and verify that the server is reachable over HTTPS.

### Windows "File in Use" Errors

Verify that the Windows WebClient service is running and that the client supports WebDAV locking correctly.

### Uploads Fail for Large Files

Check your PHP configuration:

```ini
upload_max_filesize
post_max_size
max_execution_time
memory_limit
```

Ensure these values are appropriate for the files being uploaded.

### Permission Errors

Verify that the configured storage, lock, and log directories are writable by the web server process.

---

## License

Released under the MIT License.

See the `LICENSE` file for details.
