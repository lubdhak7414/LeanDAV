<?php
/**
 * UI request handler.
 *
 * Routes UI requests to appropriate actions or dashboard.
 */

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
    // Detect post_max_size overflow: PHP discards ALL $_POST data when the
    // request body exceeds post_max_size, so csrf_token disappears and CSRF
    // validation fails with a misleading error. Detect this early.
    if ($action === 'upload' && empty($_POST) && !empty($_FILES)) {
        $post_max = ini_get('post_max_size');
        http_response_code(413);
        echo 'Upload failed: file exceeds server limit (' . $post_max . ' max). Increase post_max_size and upload_max_filesize in php.ini.';
        return;
    }

    // CSRF protection for all mutating actions
    if (in_array($action, ['upload', 'chunk-upload', 'mkdir', 'delete', 'rename', 'zip-download', 'unzip'])) {
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
    // For mkdir, validate path stays within storage even if target doesn't exist yet
    if ($resolved === false && $action === 'mkdir') {
        $storage_real = realpath($storage);
        $check_path = $storage . '/' . ltrim($path, '/');
        $normalized = realpath(dirname($check_path));
        if ($storage_real === false || $normalized === false || !str_starts_with($normalized, $storage_real)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
    }
    $full_path = $resolved !== false ? $resolved : $storage . '/' . ltrim($path, '/');

    switch ($action) {
        case 'download':
            handle_download_action($full_path, $path, $config);
            break;
        case 'upload':
            handle_upload_action($full_path, $path, $config);
            break;
        case 'chunk-upload':
            handle_chunk_upload_action($full_path, $path, $config);
            break;
        case 'mkdir':
            handle_mkdir_action($full_path, $path, $config);
            break;
        case 'delete':
            handle_delete_action($full_path, $path, $config);
            break;
        case 'rename':
            handle_rename_action($full_path, $path, $config);
            break;
        case 'zip-download':
            handle_zip_download_action($full_path, $path, $config);
            break;
        case 'unzip':
            handle_unzip_action($full_path, $path, $config);
            break;
        case 'logout':
            handle_logout_action();
            break;
        default:
            http_response_code(400);
            echo 'Unknown action';
            break;
    }
}

/**
 * Handle file download action.
 *
 * @param string $full_path Resolved filesystem path
 * @param string $path Request path
 * @param array $config Configuration array
 * @return void
 */
function handle_download_action(string $full_path, string $path, array $config): void {
    if (!file_exists($full_path) || is_dir($full_path)) {
        http_response_code(404);
        echo 'File not found';
        return;
    }

    $mime = detect_mime($full_path);
    $size = @filesize($full_path);
    if ($size === false) { $size = 0; }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    $dl_name = rawurlencode(basename($full_path));
    header("Content-Disposition: attachment; filename*=UTF-8''{$dl_name}");
    header('X-Content-Type-Options: nosniff');

    log_request($config, 'DOWNLOAD', $path, 200);

    // Stream file in chunks to handle large files without memory exhaustion
    flush();
    if (ob_get_level()) { ob_end_flush(); }
    set_time_limit(0);

    $handle = fopen($full_path, 'rb');
    if (!$handle) {
        http_response_code(500);
        return;
    }
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
    exit;
}

/**
 * Handle file upload action.
 *
 * @param string $full_path Resolved filesystem path
 * @param string $path Request path
 * @param array $config Configuration array
 * @return void
 */
function handle_upload_action(string $full_path, string $path, array $config): void {
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
        // Use 413 for size-related errors, 400 for others
        $is_size_error = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE]);
        http_response_code($is_size_error ? 413 : 400);
        $msg = upload_error_message($file['error']);
        if ($is_size_error) {
            $msg .= ' (php.ini: upload_max_filesize=' . ini_get('upload_max_filesize') . ', post_max_size=' . ini_get('post_max_size') . ')';
        }
        echo $msg;
        return;
    }

    // Check file size
    if ($file['size'] > $config['max_upload_size']) {
        http_response_code(507);
        echo 'File too large';
        return;
    }

    // Append sanitized filename to path
    $safe_name = sanitize_name($file['name']);
    if ($safe_name === false || $safe_name === '') {
        http_response_code(400);
        echo 'Invalid filename';
        return;
    }
    if (is_dir($full_path) || substr($full_path, -1) === '/') {
        $full_path = rtrim($full_path, '/') . '/' . $safe_name;
    }

    // Ensure target directory exists
    $target_dir = dirname($full_path);
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $full_path)) {
        log_request($config, 'UPLOAD', $path . '/' . $safe_name, 201);
        http_response_code(201);
        echo 'File uploaded successfully';
    } else {
        log_request($config, 'UPLOAD', $path . '/' . $safe_name, 500, 'Failed to move file');
        http_response_code(500);
        echo 'Failed to move uploaded file';
    }
}

/**
 * Handle chunked file upload. Receives individual chunks and assembles
 * them into the final file, bypassing PHP upload size limits entirely.
 */
function handle_chunk_upload_action(string $full_path, string $path, array $config): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo 'Method not allowed';
        return;
    }

    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!ui_csrf_validate($token)) {
        http_response_code(403);
        echo 'Invalid CSRF token';
        return;
    }

    // Required fields: file name, chunk index, total chunks, total size
    $file_name  = $_POST['file_name'] ?? '';
    $chunk_idx  = isset($_POST['chunk_index']) ? (int) $_POST['chunk_index'] : -1;
    $total_chunks = isset($_POST['total_chunks']) ? (int) $_POST['total_chunks'] : 0;
    $total_size = isset($_POST['total_size']) ? (int) $_POST['total_size'] : 0;

    if ($file_name === '' || $chunk_idx < 0 || $total_chunks < 1 || $total_size < 1) {
        http_response_code(400);
        echo 'Missing chunk parameters';
        return;
    }

    // Validate and sanitize filename
    $safe_name = sanitize_name($file_name);
    if ($safe_name === false || $safe_name === '') {
        http_response_code(400);
        echo 'Invalid filename';
        return;
    }

    // Enforce total file size limit
    if ($total_size > $config['max_upload_size']) {
        http_response_code(413);
        echo 'File too large';
        return;
    }

    // Resolve target path
    if (is_dir($full_path) || substr($full_path, -1) === '/') {
        $full_path = rtrim($full_path, '/') . '/' . $safe_name;
    }

    // Use a temp directory for chunks: data/.chunks/<upload_id>/
    $upload_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $file_name . '_' . $total_size);
    $chunk_dir = $config['storage_path'] . '/.chunks/' . $upload_id;

    if (!is_dir($chunk_dir)) {
        mkdir($chunk_dir, 0755, true);
    }

    // Save chunk
    if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo 'Chunk upload failed';
        return;
    }

    $chunk_path = $chunk_dir . '/' . $chunk_idx;
    if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_path)) {
        http_response_code(500);
        echo 'Failed to save chunk';
        return;
    }

    // Check if all chunks have arrived
    $saved_chunks = 0;
    for ($i = 0; $i < $total_chunks; $i++) {
        if (file_exists($chunk_dir . '/' . $i)) {
            $saved_chunks++;
        }
    }

    if ($saved_chunks < $total_chunks) {
        // More chunks expected
        http_response_code(206);
        echo json_encode(['status' => 'chunk', 'received' => $saved_chunks, 'total' => $total_chunks]);
        return;
    }

    // All chunks received — assemble final file
    $target_dir = dirname($full_path);
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $final = @fopen($full_path, 'wb');
    if (!$final) {
        http_response_code(500);
        echo 'Failed to create final file';
        return;
    }

    for ($i = 0; $i < $total_chunks; $i++) {
        $cpath = $chunk_dir . '/' . $i;
        $cf = @fopen($cpath, 'rb');
        if (!$cf) {
            fclose($final);
            http_response_code(500);
            echo 'Failed to read chunk ' . $i;
            return;
        }
        while (!feof($cf)) {
            fwrite($final, fread($cf, 8192));
        }
        fclose($cf);
        unlink($cpath); // clean up chunk
    }

    fclose($final);

    // Remove chunk directory
    rmdir($chunk_dir);

    log_request($config, 'UPLOAD', $path . '/' . $safe_name, 201);
    http_response_code(201);
    echo json_encode(['status' => 'complete', 'name' => $safe_name]);
}

/**
 * Handle directory creation action.
 *
 * @param string $full_path Resolved filesystem path
 * @param string $path Request path
 * @param array $config Configuration array
 * @return void
 */
function handle_mkdir_action(string $full_path, string $path, array $config): void {
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
}

/**
 * Handle file/directory deletion action.
 *
 * @param string $full_path Resolved filesystem path
 * @param string $path Request path
 * @param array $config Configuration array
 * @return void
 */
function handle_delete_action(string $full_path, string $path, array $config): void {
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
}

/**
 * Handle file/directory rename action.
 *
 * @param string $full_path Resolved filesystem path
 * @param string $path Request path
 * @param array $config Configuration array
 * @return void
 */
function handle_rename_action(string $full_path, string $path, array $config): void {
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
    // Prevent renaming to a path containing path separators
    if (str_contains($new_name, '/') || str_contains($new_name, '\\')) {
        http_response_code(400);
        echo 'Invalid name';
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
}

/**
 * Handle zip-download action — creates a ZIP of selected files and streams it.
 *
 * @param string $full_path Resolved filesystem path (directory context)
 * @param string $path Request path
 * @param array $config Configuration array
 * @return void
 */
function handle_zip_download_action(string $full_path, string $path, array $config): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo 'Method not allowed';
        return;
    }

    $paths = $_POST['paths'] ?? [];
    if (empty($paths) || !is_array($paths)) {
        http_response_code(400);
        echo 'No files selected';
        return;
    }

    $storage = rtrim($config['storage_path'], '/');
    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'webdav_zip_');

    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        echo 'Failed to create ZIP';
        @unlink($tmp);
        return;
    }

    $added = 0;
    foreach ($paths as $relPath) {
        $relPath = trim($relPath, '/');
        $item_full = resolve_path('/' . $relPath, $config['storage_path']);
        if ($item_full === false) {
            $item_full = $storage . '/' . $relPath;
            if (!file_exists($item_full)) continue;
        }

        // Security: ensure resolved path is inside storage
        $storage_real = realpath($storage);
        $item_real = realpath($item_full);
        if ($storage_real === false || $item_real === false || !str_starts_with($item_real, $storage_real)) {
            continue;
        }

        if (is_file($item_full)) {
            $arc_name = basename($item_full);
            $zip->addFile($item_full, $arc_name);
            $added++;
        } elseif (is_dir($item_full)) {
            $dir_name = basename($item_full) . '/';
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($item_full, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                $relative = substr($file->getPathname(), strlen($item_full) + 1);
                $zip->addFile($file->getPathname(), $dir_name . $relative);
                $added++;
            }
        }
    }

    $zip->close();

    if ($added === 0) {
        http_response_code(404);
        echo 'No valid files found';
        @unlink($tmp);
        return;
    }

    $size = @filesize($tmp);
    $zip_name = rawurlencode('files_' . date('Y-m-d_H-i-s') . '.zip');

    header('Content-Type: application/zip');
    header('Content-Length: ' . $size);
    header("Content-Disposition: attachment; filename*=UTF-8''{$zip_name}");
    header('X-Content-Type-Options: nosniff');

    log_request($config, 'ZIP-DOWNLOAD', $path . ' (' . $added . ' files)', 200);

    flush();
    if (ob_get_level()) { ob_end_flush(); }
    set_time_limit(0);

    $handle = fopen($tmp, 'rb');
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    }

    @unlink($tmp);
    exit;
}

/**
 * Handle unzip action — extracts a ZIP archive into its parent directory.
 *
 * @param string $full_path Resolved filesystem path to the ZIP file
 * @param string $path Request path
 * @param array $config Configuration array
 * @return void
 */
function handle_unzip_action(string $full_path, string $path, array $config): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo 'Method not allowed';
        return;
    }

    if (!file_exists($full_path) || !is_file($full_path)) {
        http_response_code(404);
        echo 'Archive not found';
        return;
    }

    $ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['zip', 'gz', 'tar', 'bz2'])) {
        http_response_code(400);
        echo 'Unsupported archive format';
        return;
    }

    $storage_real = realpath(rtrim($config['storage_path'], '/'));
    $file_real = realpath($full_path);
    if ($storage_real === false || $file_real === false || !str_starts_with($file_real, $storage_real)) {
        http_response_code(403);
        echo 'Forbidden';
        return;
    }

    if ($ext === 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($full_path) !== true) {
            http_response_code(500);
            echo 'Failed to open ZIP archive';
            return;
        }
        $zip->extractTo(dirname($full_path));
        $zip->close();
    } else {
        $extract_to = dirname($full_path);
        $cmd = null;
        if ($ext === 'gz' || $ext === 'tar') {
            $cmd = 'tar -xzf ' . escapeshellarg($full_path) . ' -C ' . escapeshellarg($extract_to) . ' 2>&1';
        } elseif ($ext === 'bz2') {
            $cmd = 'tar -xjf ' . escapeshellarg($full_path) . ' -C ' . escapeshellarg($extract_to) . ' 2>&1';
        }
        if ($cmd) {
            $output = [];
            $return_code = 0;
            exec($cmd, $output, $return_code);
            if ($return_code !== 0) {
                http_response_code(500);
                echo 'Extraction failed: ' . implode("\n", $output);
                return;
            }
        } else {
            http_response_code(500);
            echo 'Extraction not supported on this server';
            return;
        }
    }

    log_request($config, 'UNZIP', $path, 200);
    http_response_code(200);
    echo 'Archive extracted successfully';
}

/**
 * Handle logout — clear HTTP Basic Auth session.
 *
 * Sends a 401 response with WWW-Authenticate header which prompts the browser
 * to clear cached credentials and show the login dialog.
 *
 * @return void
 */
function handle_logout_action(): void {
    do_logout();
    header('Location: /');
    exit;
}
