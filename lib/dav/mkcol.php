<?php
/**
 * MKCOL request handler.
 *
 * Creates new collections (directories).
 */

/**
 * Handle MKCOL request.
 *
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_mkcol(array $config, string $path): void {
    // Check if resource already exists
    if (file_exists($path)) {
        dav_error(405, 'Method Not Allowed');
        return;
    }

    // Ensure parent directory exists
    $parent = dirname($path);
    if (!is_dir($parent)) {
        dav_error(409, 'Conflict');
        return;
    }

    // Create directory
    if (mkdir($path)) {
        http_response_code(201);
        header('Content-Length: 0');
    } else {
        dav_error(500, 'Internal Server Error');
    }
}
