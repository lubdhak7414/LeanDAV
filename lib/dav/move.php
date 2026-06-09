<?php
/**
 * MOVE request handler.
 *
 * Moves or renames resources with lock and overwrite checking.
 */

/**
 * Handle MOVE request.
 *
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_move(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }

    // Get Destination header
    $destination = $_SERVER['HTTP_DESTINATION'] ?? '';
    if (empty($destination)) {
        dav_error(400, 'Bad Request');
        return;
    }

    // Resolve destination path
    $dest_path = resolve_path($destination, $config['storage_path']);
    if ($dest_path === false) {
        dav_error(403, 'Forbidden');
        return;
    }

    // Check if destination already exists
    $overwrite = $_SERVER['HTTP_OVERWRITE'] ?? 'T';
    if (file_exists($dest_path) && strtoupper($overwrite) !== 'T') {
        dav_error(412, 'Precondition Failed');
        return;
    }

    // Check if source is locked
    if (is_locked($path, $config['lock_dir'])) {
        $if_header = $_SERVER['HTTP_IF'] ?? '';
        if (empty($if_header)) {
            dav_error(423, 'Locked');
            return;
        }
    }

    // Check if destination is locked
    if (is_locked($dest_path, $config['lock_dir'])) {
        $if_header = $_SERVER['HTTP_IF'] ?? '';
        if (empty($if_header)) {
            dav_error(423, 'Locked');
            return;
        }
    }

    // Perform move
    if (rename($path, $dest_path)) {
        http_response_code(201);
        header('Content-Length: 0');
    } else {
        dav_error(500, 'Internal Server Error');
    }
}
