<?php
/**
 * DELETE request handler.
 *
 * Removes files and directories with lock checking.
 */

/**
 * Handle DELETE request.
 *
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_delete(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }

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

    // Recursive delete
    if (is_file($path)) {
        unlink($path);
    } else {
        recursive_delete($path);
    }

    http_response_code(204);
    header('Content-Length: 0');
}

/**
 * Recursively delete directory and all contents.
 *
 * @param string $path Directory path
 * @return void
 */
function recursive_delete(string $path): void {
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getRealPath());
        } else {
            unlink($item->getRealPath());
        }
    }

    rmdir($path);
}
