<?php
/**
 * Management UI for WebDAV server.
 *
 * Browser-based dashboard for file management.
 */

/**
 * Generate a CSRF token for form/request protection.
 *
 * @return string The token
 */
function ui_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['ui_csrf_token'])) {
        $_SESSION['ui_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ui_csrf_token'];
}

/**
 * Validate a CSRF token.
 *
 * @param string $token Token to validate
 * @return bool True if valid
 */
function ui_csrf_validate(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['ui_csrf_token']) && hash_equals($_SESSION['ui_csrf_token'], $token);
}

/**
 * Human-readable upload error message.
 *
 * @param int $code PHP UPLOAD_ERR_* code
 * @return string Human-readable message
 */
function upload_error_message(int $code): string {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension',
    ];
    return $messages[$code] ?? "Upload error code: {$code}";
}

/**
 * Sanitize a rename/new-name string for safe filesystem use.
 *
 * @param string $name Raw name input
 * @return string|false Sanitized name, or false if invalid
 */
function sanitize_name(string $name): string|false {
    $name = trim($name);
    if ($name === '' || $name === '.' || $name === '..') {
        return false;
    }
    // Strip null bytes and path separators
    $name = str_replace(["\0", '/', '\\'], '', $name);
    // Strip Windows-reserved names (CON, PRN, AUX, NUL, COM1–COM9, LPT1–LPT9)
    $base = strtoupper(pathinfo($name, PATHINFO_FILENAME));
    if (in_array($base, ['CON','PRN','AUX','NUL','COM1','COM2','COM3','COM4','COM5','COM6','COM7','COM8','COM9','LPT1','LPT2','LPT3','LPT4','LPT5','LPT6','LPT7','LPT8','LPT9'], true)) {
        return false;
    }
    return $name;
}

/**
 * Resolve a $_GET['path'] value, decoding URL encoding.
 *
 * @param string $raw Raw GET path
 * @return string Decoded path
 */
function ui_resolve_get_path(string $raw): string {
    return rawurldecode($raw);
}

/**
 * Map a file extension to a display emoji icon.
 *
 * @param string $ext File extension (lowercase)
 * @param bool $is_dir Whether entry is a directory
 * @return string Emoji icon
 */
function file_type_icon(string $ext, bool $is_dir): string {
    if ($is_dir) {
        return '📁';
    }
    $icon_map = [
        // Images
        'png'  => '🖼️', 'jpg'  => '🖼️', 'jpeg' => '🖼️', 'gif'  => '🖼️',
        'webp' => '🖼️', 'svg'  => '🖼️', 'bmp'  => '🖼️', 'ico'  => '🖼️',
        // Video
        'mp4'  => '🎬', 'webm' => '🎬', 'mkv'  => '🎬', 'avi'  => '🎬',
        'mov'  => '🎬', 'wmv'  => '🎬',
        // Audio
        'mp3'  => '🎵', 'wav'  => '🎵', 'flac' => '🎵', 'aac'  => '🎵',
        'ogg'  => '🎵', 'wma'  => '🎵',
        // Archives
        'zip'  => '📦', 'rar'  => '📦', '7z'   => '📦', 'tar'  => '📦',
        'gz'   => '📦', 'bz2'  => '📦',
        // Documents
        'pdf'  => '📕', 'doc'  => '📘', 'docx' => '📘', 'xls'  => '📗',
        'xlsx' => '📗', 'ppt'  => '📙', 'pptx' => '📙', 'csv'  => '📊',
        // Code
        'php'  => '🔧', 'js'   => '🔧', 'ts'   => '🔧', 'py'   => '🔧',
        'java' => '🔧', 'c'    => '🔧', 'cpp'  => '🔧', 'h'    => '🔧',
        'html' => '🔧', 'css'  => '🔧', 'json' => '🔧', 'xml'  => '🔧',
        'yml'  => '🔧', 'yaml' => '🔧', 'sql'  => '🔧', 'md'   => '🔧',
        // Text / Config
        'txt'  => '📄', 'log'  => '📄', 'ini'  => '📄', 'conf' => '📄',
    ];
    return $icon_map[$ext] ?? '📄';
}

/**
 * Handle UI requests (browser-based management).
 *
 * @param array $config Configuration array
 * @return void
 */
function handle_ui_request(array $config): void {
    $action = $_GET['action'] ?? null;
    $path = ui_resolve_get_path($_GET['path'] ?? '/');

    // Handle UI actions
    if ($action) {
        handle_ui_action($config, $action, $path);
        return;
    }

    // Render dashboard
    render_dashboard($config, $path);
}

/**
 * Handle UI actions (upload, download, mkdir, delete, rename).
 *
 * @param array $config Configuration array
 * @param string $action Action to perform
 * @param string $path Resource path
 * @return void
 */
function handle_ui_action(array $config, string $action, string $path): void {
    // CSRF protection for all mutating actions
    if (in_array($action, ['upload', 'mkdir', 'delete', 'rename'])) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!ui_csrf_validate($token)) {
            http_response_code(403);
            echo 'Invalid CSRF token';
            return;
        }
    }

    // Validate path via traversal protection
    $storage = rtrim($config['storage_path'], '/');
    $resolved = resolve_path($path, $config['storage_path']);
    if ($resolved === false && $action !== 'mkdir') {
        http_response_code(403);
        echo 'Forbidden';
        return;
    }
    $full_path = $resolved !== false ? $resolved : $storage . '/' . ltrim($path, '/');

    switch ($action) {
        case 'download':
            // Stream file download
            if (!file_exists($full_path) || is_dir($full_path)) {
                http_response_code(404);
                echo 'File not found';
                return;
            }

            $mime = detect_mime($full_path);
            $size = filesize($full_path);

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . $size);
            $dl_name = rawurlencode(basename($full_path));
            header("Content-Disposition: attachment; filename*=UTF-8''{$dl_name}");
            header('X-Content-Type-Options: nosniff');

            log_request($config, 'DOWNLOAD', $path, 200);
            readfile($full_path);
            break;

        case 'upload':
            // Handle multipart upload
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo 'Method not allowed';
                return;
            }

            if (!isset($_FILES['file'])) {
                http_response_code(400);
                echo 'No file uploaded';
                return;
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                http_response_code(500);
                echo upload_error_message($file['error']);
                return;
            }

            // Check file size
            if ($file['size'] > $config['max_upload_size']) {
                http_response_code(507);
                echo 'File too large';
                return;
            }

            // Append filename to path
            if (is_dir($full_path) || substr($full_path, -1) === '/') {
                $full_path = rtrim($full_path, '/') . '/' . $file['name'];
            }

            // Ensure target directory exists
            $target_dir = dirname($full_path);
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                log_request($config, 'UPLOAD', $path . '/' . $file['name'], 201);
                http_response_code(201);
                echo 'File uploaded successfully';
            } else {
                log_request($config, 'UPLOAD', $path . '/' . $file['name'], 500, 'Failed to move file');
                http_response_code(500);
                echo 'Failed to move uploaded file';
            }
            break;

        case 'mkdir':
            // Create directory
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo 'Method not allowed';
                return;
            }

            if (file_exists($full_path)) {
                http_response_code(409);
                echo 'Directory already exists';
                return;
            }

            if (mkdir($full_path, 0755, true)) {
                log_request($config, 'MKDIR', $path, 201);
                http_response_code(201);
                echo 'Directory created';
            } else {
                log_request($config, 'MKDIR', $path, 500, 'mkdir failed');
                http_response_code(500);
                echo 'Failed to create directory';
            }
            break;

        case 'delete':
            // Delete file or directory
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo 'Method not allowed';
                return;
            }

            if (!file_exists($full_path)) {
                http_response_code(404);
                echo 'File not found';
                return;
            }

            if (is_file($full_path)) {
                unlink($full_path);
            } else {
                recursive_delete($full_path);
            }

            log_request($config, 'DELETE', $path, 204);
            http_response_code(204);
            break;

        case 'rename':
            // Rename file or directory
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo 'Method not allowed';
                return;
            }

            $new_name = sanitize_name($_POST['new_name'] ?? '');
            if ($new_name === false) {
                http_response_code(400);
                echo 'Invalid name';
                return;
            }
            if (empty($new_name)) {
                http_response_code(400);
                echo 'New name required';
                return;
            }

            $new_path = dirname($full_path) . '/' . $new_name;
            if (file_exists($new_path)) {
                http_response_code(409);
                echo 'Target already exists';
                return;
            }

            if (rename($full_path, $new_path)) {
                log_request($config, 'RENAME', $path . ' → ' . $new_name, 200);
                http_response_code(200);
                echo 'Renamed successfully';
            } else {
                log_request($config, 'RENAME', $path, 500, 'rename failed');
                http_response_code(500);
                echo 'Failed to rename';
            }
            break;

        default:
            http_response_code(400);
            echo 'Unknown action';
            break;
    }
}

/**
 * Render the management dashboard.
 *
 * @param array $config Configuration array
 * @param string $path Current directory path
 * @return void
 */
function render_dashboard(array $config, string $path): void {
    $storage = rtrim($config['storage_path'], '/');
    $full_path = $storage . '/' . ltrim($path, '/');

    // Ensure path exists
    if (!is_dir($full_path)) {
        $full_path = $storage;
        $path = '/';
    }

    // Scan directory and compute stats in one pass
    $entries_raw = scan_directory($full_path, $config['hide_dotfiles'] ?? true);
    $total_files = 0;
    $total_size = 0;
    $entry_meta = [];  // pre-compute metadata for each entry
    foreach ($entries_raw as $entry) {
        $entry_path = $full_path . '/' . $entry;
        $is_dir = is_dir($entry_path);
        $size = 0;
        $mtime = 0;
        if (!$is_dir) {
            $total_files++;
            $size = filesize($entry_path);
            $total_size += $size;
        }
        $mtime = filemtime($entry_path);
        $ext = $is_dir ? '' : strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        $entry_meta[] = [
            'name'   => $entry,
            'is_dir' => $is_dir,
            'size'   => $size,
            'mtime'  => $mtime,
            'ext'    => $ext,
        ];
    }

    // Build breadcrumb
    $breadcrumb = build_breadcrumb($path);

    // Get username for display
    $username = $config['auth']['username'] ?? 'admin';

    // Get max upload size in MB
    $max_upload_mb = round($config['max_upload_size'] / 1048576);

    // CSRF token
    $csrf = ui_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebDAV Server</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'JetBrains Mono', monospace;
            background: #0a0a0a;
            color: #e8e4dc;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #141414;
            border: 1px solid #2a2a2a;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 700;
            color: #e8a530;
        }

        .header .user {
            font-size: 12px;
            color: #5a5550;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
            padding: 15px 20px;
            background: #141414;
            border: 1px solid #2a2a2a;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #e8a530;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb .separator {
            color: #5a5550;
            margin: 0 4px;
        }

        .breadcrumb .up-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            color: #e8a530;
            text-decoration: none;
            font-size: 14px;
            margin-right: 8px;
            transition: background 0.15s, border-color 0.15s;
            flex-shrink: 0;
        }

        .breadcrumb .up-btn:hover {
            background: #222;
            border-color: #e8a530;
            text-decoration: none;
        }

        .main {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .main {
                grid-template-columns: 1fr;
            }

            .content-header {
                grid-template-columns: 1fr 80px 60px !important;
            }

            .file-item {
                grid-template-columns: 1fr 80px 60px !important;
            }

            .file-item .actions {
                position: static !important;
                opacity: 1 !important;
            }

            .file-item .modified {
                display: none;
            }

            .content-header span:nth-child(3) {
                display: none;
            }
        }

        .sidebar {
            background: #141414;
            border: 1px solid #2a2a2a;
            padding: 20px;
        }

        .sidebar h2 {
            font-size: 14px;
            font-weight: 500;
            color: #5a5550;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .upload-zone {
            border: 2px dashed #2a2a2a;
            border-radius: 8px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: #e8a530;
            background: rgba(232, 165, 48, 0.05);
        }

        .upload-zone .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .upload-zone .text {
            font-size: 12px;
            color: #5a5550;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #e8a530;
            color: #0a0a0a;
            border: none;
            border-radius: 6px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 10px;
        }

        .btn:hover {
            background: #d4942a;
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #2a2a2a;
            color: #e8e4dc;
        }

        .btn-secondary:hover {
            background: #1a1a1a;
        }

        .btn-danger {
            background: transparent;
            border: 1px solid #5a2020;
            color: #e85050;
        }

        .btn-danger:hover {
            background: #2a1010;
        }

        .stats {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #2a2a2a;
            font-size: 12px;
            color: #5a5550;
        }

        .stats .value {
            color: #e8e4dc;
            font-weight: 500;
        }

        /* Search / filter */
        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 12px;
            background: #0a0a0a;
            border: 1px solid #2a2a2a;
            border-radius: 6px;
            color: #e8e4dc;
            font-family: inherit;
            font-size: 12px;
            transition: border-color 0.2s;
        }

        .search-box input::placeholder {
            color: #3a3a3a;
        }

        .search-box input:focus {
            outline: none;
            border-color: #e8a530;
        }

        /* Sort controls */
        .sort-controls {
            display: flex;
            gap: 6px;
            margin-bottom: 12px;
        }

        .sort-btn {
            padding: 4px 10px;
            background: transparent;
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            color: #5a5550;
            font-family: inherit;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .sort-btn:hover {
            color: #e8e4dc;
            border-color: #5a5550;
        }

        .sort-btn.active {
            color: #e8a530;
            border-color: #e8a530;
        }

        .content {
            background: #141414;
            border: 1px solid #2a2a2a;
        }

        .content-header {
            display: grid;
            grid-template-columns: 1fr 100px 150px 100px;
            padding: 15px 20px;
            border-bottom: 1px solid #2a2a2a;
            font-size: 11px;
            color: #5a5550;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .file-list {
            min-height: 400px;
        }

        .file-item {
            display: grid;
            grid-template-columns: 1fr 100px 150px 100px;
            padding: 12px 20px;
            border-bottom: 1px solid #1a1a1a;
            font-size: 13px;
            transition: background 0.1s;
            cursor: pointer;
        }

        .file-item:hover {
            background: #1a1a1a;
        }

        .file-item .name {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow: hidden;
        }

        .file-item .name a,
        .file-item .name span.name-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-item .icon {
            font-size: 16px;
            flex-shrink: 0;
        }

        .file-item .size,
        .file-item .modified {
            color: #5a5550;
        }

        .file-item .actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .file-item:hover .actions {
            opacity: 1;
        }

        .file-item .actions button {
            background: transparent;
            border: none;
            color: #5a5550;
            cursor: pointer;
            padding: 4px;
            font-size: 14px;
        }

        .file-item .actions button:hover {
            color: #e8a530;
        }

        .file-item .actions button.delete:hover {
            color: #e85050;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            color: #5a5550;
            font-size: 14px;
        }

        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #141414;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }

        .modal-content h3 {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .modal-content input {
            width: 100%;
            padding: 12px;
            background: #0a0a0a;
            border: 1px solid #2a2a2a;
            border-radius: 6px;
            color: #e8e4dc;
            font-family: inherit;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .modal-content input:focus {
            outline: none;
            border-color: #e8a530;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-actions .btn {
            width: auto;
            padding: 10px 20px;
        }

        /* Progress */
        .progress {
            display: none;
            margin-top: 15px;
        }

        .progress.active {
            display: block;
        }

        .progress-bar {
            height: 4px;
            background: #2a2a2a;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: #e8a530;
            transition: width 0.2s;
            width: 0%;
        }

        .progress-text {
            font-size: 11px;
            color: #5a5550;
            margin-top: 8px;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #141414;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 15px 20px;
            font-size: 13px;
            z-index: 2000;
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
        }

        .toast.active {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.success {
            border-color: #4caf50;
        }

        .toast.error {
            border-color: #e85050;
        }

        #fileInput {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WebDAV Server</h1>
            <span class="user">user: <?php echo htmlspecialchars($username); ?></span>
        </div>

        <div class="breadcrumb">
            <?php
            // Up button (skip at root)
            if ($path !== '/') {
                $parent = rtrim(dirname(rtrim($path, '/')), '/') ?: '/';
                echo '<a class="up-btn" href="?path=' . htmlspecialchars(rawurlencode($parent)) . '" title="Go up">↑</a>';
            }
            ?>
            <?php echo $breadcrumb; ?>
        </div>

        <div class="main">
            <div class="sidebar">
                <h2>Actions</h2>

                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍  Filter files…" autocomplete="off">
                </div>

                <div class="upload-zone" id="uploadZone">
                    <div class="icon">📁</div>
                    <div class="text">Drag & drop files here<br>or click to select</div>
                </div>

                <input type="file" id="fileInput" multiple>

                <button class="btn" onclick="document.getElementById('fileInput').click()">
                    Select Files
                </button>

                <button class="btn btn-secondary" onclick="showNewFolderModal()">
                    + New Folder
                </button>

                <div class="progress" id="progress">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText">Uploading…</div>
                </div>

                <div class="stats">
                    <div>Files: <span class="value"><?php echo $total_files; ?></span></div>
                    <div>Size: <span class="value"><?php echo format_size($total_size); ?></span></div>
                </div>
            </div>

            <div class="content">
                <div class="content-header">
                    <span>Name</span>
                    <span>Size</span>
                    <span>Modified</span>
                    <span>Actions</span>
                </div>

                <div class="sort-controls" style="padding: 10px 20px 0;">
                    <button class="sort-btn active" data-sort="name" onclick="sortFiles('name', this)">Name</button>
                    <button class="sort-btn" data-sort="size" onclick="sortFiles('size', this)">Size</button>
                    <button class="sort-btn" data-sort="date" onclick="sortFiles('date', this)">Date</button>
                </div>

                <div class="file-list" id="fileList">
                    <?php if (empty($entry_meta)): ?>
                        <div class="empty-state" id="emptyState">
                            <div class="icon">📭</div>
                            <div>No files yet</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($entry_meta as $meta): ?>
                            <?php
                            $entry = $meta['name'];
                            $is_dir = $meta['is_dir'];
                            $entry_url = rtrim($path, '/') . '/' . rawurlencode($entry);
                            if ($is_dir) $entry_url .= '/';
                            ?>
                            <div class="file-item"
                                 data-path="<?php echo htmlspecialchars($entry_url); ?>"
                                 data-type="<?php echo $is_dir ? 'dir' : 'file'; ?>"
                                 data-name="<?php echo htmlspecialchars(strtolower($entry)); ?>"
                                 data-size="<?php echo $meta['size']; ?>"
                                 data-mtime="<?php echo $meta['mtime']; ?>"
                                 data-search="<?php echo htmlspecialchars(strtolower($entry)); ?>">
                                <div class="name">
                                    <span class="icon"><?php echo file_type_icon($meta['ext'], $is_dir); ?></span>
                                    <?php if ($is_dir): ?>
                                        <a href="?path=<?php echo htmlspecialchars(rtrim($path, '/') . '/' . $entry); ?>"><?php echo htmlspecialchars($entry); ?></a>
                                    <?php else: ?>
                                        <span class="name-text"><?php echo htmlspecialchars($entry); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="size"><?php echo $is_dir ? '—' : format_size($meta['size']); ?></div>
                                <div class="modified"><?php echo date('M d, Y', $meta['mtime']); ?></div>
                                <div class="actions">
                                    <?php if (!$is_dir): ?>
                                        <button onclick="downloadFile('<?php echo htmlspecialchars($entry_url); ?>')" title="Download">⬇</button>
                                    <?php endif; ?>
                                    <button onclick="showRenameModal('<?php echo htmlspecialchars(addcslashes($entry, "'\\\n\r") ); ?>', '<?php echo htmlspecialchars($entry_url); ?>')" title="Rename">✏️</button>
                                    <button class="delete" onclick="deleteItem('<?php echo htmlspecialchars($entry_url); ?>', '<?php echo htmlspecialchars(addcslashes($entry, "'\\\n\r") ); ?>')" title="Delete">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Folder Modal -->
    <div class="modal" id="newFolderModal">
        <div class="modal-content">
            <h3>New Folder</h3>
            <input type="text" id="newFolderName" placeholder="Folder name" autocomplete="off">
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('newFolderModal')">Cancel</button>
                <button class="btn" onclick="createFolder()">Create</button>
            </div>
        </div>
    </div>

    <!-- Rename Modal -->
    <div class="modal" id="renameModal">
        <div class="modal-content">
            <h3>Rename</h3>
            <input type="text" id="renameName" placeholder="New name" autocomplete="off">
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('renameModal')">Cancel</button>
                <button class="btn" onclick="renameItem()">Rename</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        // Current path and CSRF token
        const currentPath = <?php echo json_encode($path); ?>;
        const maxUploadSize = <?php echo (int)$config['max_upload_size']; ?>;
        const csrfToken = <?php echo json_encode($csrf); ?>;
        const uploadMaxMb = <?php echo (int)$max_upload_mb; ?>;

        // ===== Upload Zone =====
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');

        uploadZone.addEventListener('click', () => fileInput.click());

        // Also support drag-and-drop on the entire file list area
        const fileList = document.getElementById('fileList');

        function setupDragZone(el) {
            el.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadZone.classList.add('dragover');
            });
            el.addEventListener('dragleave', (e) => {
                // Only remove if leaving the element entirely
                if (!el.contains(e.relatedTarget)) {
                    uploadZone.classList.remove('dragover');
                }
            });
            el.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
                if (e.dataTransfer.files.length > 0) {
                    handleFiles(e.dataTransfer.files);
                }
            });
        }

        setupDragZone(uploadZone);
        setupDragZone(fileList);

        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
            // Reset input so selecting the same file again triggers change
            fileInput.value = '';
        });

        // ===== File Upload with Real Progress =====
        let uploadQueue = [];
        let uploadIndex = 0;
        let uploading = false;

        async function handleFiles(files) {
            for (const file of files) {
                if (file.size > maxUploadSize) {
                    showToast('File too large: ' + file.name + ' (' + formatBytes(file.size) + ' > ' + formatBytes(maxUploadSize) + ')', 'error');
                    continue;
                }
                uploadQueue.push(file);
            }
            if (!uploading) {
                processUploadQueue();
            }
        }

        async function processUploadQueue() {
            if (uploadIndex >= uploadQueue.length) {
                uploadQueue = [];
                uploadIndex = 0;
                return;
            }

            uploading = true;
            const total = uploadQueue.length;
            const file = uploadQueue[uploadIndex];
            uploadIndex++;

            await uploadFile(file, uploadIndex, total);

            await processUploadQueue();
        }

        function uploadFile(file, current, total) {
            return new Promise((resolve) => {
                const progress = document.getElementById('progress');
                const progressFill = document.getElementById('progressFill');
                const progressText = document.getElementById('progressText');

                progress.classList.add('active');
                progressText.textContent = 'Uploading ' + current + ' of ' + total + ': ' + file.name;
                progressFill.style.width = '0%';

                const xhr = new XMLHttpRequest();
                const formData = new FormData();
                formData.append('file', file);
                formData.append('csrf_token', csrfToken);

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        progressFill.style.width = pct + '%';
                        progressText.textContent = 'Uploading ' + current + ' of ' + total + ': ' + file.name + ' (' + pct + '%)';
                    }
                });

                xhr.addEventListener('load', () => {
                    progress.classList.remove('active');
                    if (xhr.status >= 200 && xhr.status < 300) {
                        showToast('Uploaded: ' + file.name, 'success');
                    } else {
                        showToast('Upload failed: ' + (xhr.responseText || xhr.statusText), 'error');
                    }
                    resolve();
                });

                xhr.addEventListener('error', () => {
                    progress.classList.remove('active');
                    showToast('Upload error for: ' + file.name, 'error');
                    resolve();
                });

                xhr.open('POST', '?action=upload&path=' + encodeURIComponent(currentPath));
                xhr.send(formData);
            });
        }

        function formatBytes(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
            return Math.round(bytes * 10) / 10 + ' ' + units[i];
        }

        // ===== Download =====
        function downloadFile(path) {
            window.location.href = '?action=download&path=' + encodeURIComponent(path);
        }

        // ===== Delete =====
        async function deleteItem(path, name) {
            if (!confirm('Delete "' + name + '"?')) {
                return;
            }

            try {
                const response = await fetch('?action=delete&path=' + encodeURIComponent(path), {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: 'csrf_token=' + encodeURIComponent(csrfToken)
                });

                if (response.ok) {
                    showToast('Deleted: ' + name, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    const text = await response.text();
                    showToast('Delete failed: ' + text, 'error');
                }
            } catch (error) {
                showToast('Delete error: ' + error.message, 'error');
            }
        }

        // ===== New Folder Modal =====
        function showNewFolderModal() {
            document.getElementById('newFolderModal').classList.add('active');
            const input = document.getElementById('newFolderName');
            input.value = '';
            input.focus();
        }

        async function createFolder() {
            const name = document.getElementById('newFolderName').value.trim();
            if (!name) {
                return;
            }

            const path = currentPath === '/' ? '/' + name : currentPath + '/' + name;

            try {
                const response = await fetch('?action=mkdir&path=' + encodeURIComponent(path), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'csrf_token=' + encodeURIComponent(csrfToken)
                });

                if (response.ok) {
                    showToast('Created folder: ' + name, 'success');
                    closeModal('newFolderModal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    const text = await response.text();
                    showToast('Create failed: ' + text, 'error');
                }
            } catch (error) {
                showToast('Create error: ' + error.message, 'error');
            }
        }

        // ===== Rename Modal =====
        let renamePath = '';

        function showRenameModal(currentName, path) {
            renamePath = path;
            const input = document.getElementById('renameName');
            input.value = currentName;
            document.getElementById('renameModal').classList.add('active');
            input.focus();
            input.select();
        }

        async function renameItem() {
            const newName = document.getElementById('renameName').value.trim();
            if (!newName) {
                return;
            }

            try {
                const response = await fetch('?action=rename&path=' + encodeURIComponent(renamePath), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'new_name=' + encodeURIComponent(newName) + '&csrf_token=' + encodeURIComponent(csrfToken)
                });

                if (response.ok) {
                    showToast('Renamed successfully', 'success');
                    closeModal('renameModal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    const text = await response.text();
                    showToast('Rename failed: ' + text, 'error');
                }
            } catch (error) {
                showToast('Rename error: ' + error.message, 'error');
            }
        }

        // ===== Modal Helpers =====
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // Enter key submits the active modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                if (document.getElementById('newFolderModal').classList.contains('active')) {
                    e.preventDefault();
                    createFolder();
                } else if (document.getElementById('renameModal').classList.contains('active')) {
                    e.preventDefault();
                    renameItem();
                }
            }
        });

        // ===== Toast Notifications =====
        let toastTimer = null;
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast active ' + type;

            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.classList.remove('active');
            }, 3000);
        }

        // Close modals on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
            }
        });

        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // ===== Client-side Search / Filter =====
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase().trim();
            const items = fileList.querySelectorAll('.file-item');
            items.forEach(item => {
                const name = item.getAttribute('data-search') || '';
                item.style.display = (!q || name.includes(q)) ? '' : 'none';
            });
        });

        // ===== Client-side Sorting =====
        let currentSort = 'name';
        let sortAsc = true;

        function sortFiles(field, btn) {
            if (currentSort === field) {
                sortAsc = !sortAsc;
            } else {
                currentSort = field;
                sortAsc = true;
            }

            // Update button states
            document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            btn.textContent = (field === 'name' ? 'Name' : field === 'size' ? 'Size' : 'Date') + (sortAsc ? ' ▲' : ' ▼');

            const items = Array.from(fileList.querySelectorAll('.file-item'));

            items.sort((a, b) => {
                let va, vb;

                // Always keep directories first
                const aDir = a.getAttribute('data-type') === 'dir';
                const bDir = b.getAttribute('data-type') === 'dir';
                if (aDir && !bDir) return -1;
                if (!aDir && bDir) return 1;

                switch (field) {
                    case 'name':
                        va = a.getAttribute('data-name') || '';
                        vb = b.getAttribute('data-name') || '';
                        return sortAsc ? va.localeCompare(vb) : vb.localeCompare(va);
                    case 'size':
                        va = parseInt(a.getAttribute('data-size') || '0', 10);
                        vb = parseInt(b.getAttribute('data-size') || '0', 10);
                        return sortAsc ? va - vb : vb - va;
                    case 'date':
                        va = parseInt(a.getAttribute('data-mtime') || '0', 10);
                        vb = parseInt(b.getAttribute('data-mtime') || '0', 10);
                        return sortAsc ? va - vb : vb - va;
                    default:
                        return 0;
                }
            });

            items.forEach(item => fileList.appendChild(item));
        }
    </script>
</body>
</html>
<?php
}

/**
 * Build breadcrumb HTML.
 *
 * @param string $path Current path
 * @return string HTML breadcrumb
 */
function build_breadcrumb(string $path): string {
    $parts = array_filter(explode('/', $path));
    $html = '<a href="?path=/">/</a>';

    $current = '';
    foreach ($parts as $part) {
        $current .= '/' . $part;
        $html .= '<span class="separator">›</span>';
        $html .= '<a href="?path=' . htmlspecialchars(rawurlencode($current)) . '">' . htmlspecialchars($part) . '</a>';
    }

    return $html;
}

/**
 * Format file size to human-readable string.
 *
 * @param int $bytes File size in bytes
 * @return string Formatted size
 */
function format_size(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;

    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, 1) . ' ' . $units[$i];
}
