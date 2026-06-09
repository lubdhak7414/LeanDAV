<?php
/**
 * PUT request handler.
 *
 * Handles file uploads with atomic write and lock checking.
 */

/**
 * Handle PUT request.
 *
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_put(array $config, string $path): void {
    // Check if resource is locked
    if (is_locked($path, $config['lock_dir'])) {
        // Check for valid If header
        $if_header = $_SERVER['HTTP_IF'] ?? '';
        if (empty($if_header)) {
            dav_error(423, 'Locked');
            return;
        }
        // Validate lock token
        if (!validate_lock_token($path, $if_header)) {
            dav_error(423, 'Locked');
            return;
        }
    }

    // Ensure parent directory exists
    $parent = dirname($path);
    if (!is_dir($parent)) {
        dav_error(409, 'Conflict');
        return;
    }

    // Create temporary file for atomic write
    $tmpfile = $path . '.part.' . uniqid('', true);

    // Register cleanup function for connection abort
    register_shutdown_function(function () use ($tmpfile) {
        if (connection_aborted() && file_exists($tmpfile)) {
            unlink($tmpfile);
        }
    });

    $in = fopen('php://input', 'rb');
    $out = fopen($tmpfile, 'wb');

    if ($in === false || $out === false) {
        dav_error(500, 'Internal Server Error');
        return;
    }

    // Streaming byte counter — no size limit for WebDAV (php://input streams directly)
    $written = 0;

    while (!feof($in)) {
        $chunk = fread($in, 8192);
        $written += strlen($chunk);
        fwrite($out, $chunk);
    }

    fclose($in);
    fclose($out);

    // Atomic rename
    $existed = file_exists($path);
    rename($tmpfile, $path);

    http_response_code($existed ? 204 : 201);
    header('Content-Length: 0');
}
