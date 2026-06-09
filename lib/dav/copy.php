<?php
/**
 * COPY request handler.
 *
 * Copies resources with recursive directory support.
 */

/**
 * Handle COPY request.
 *
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_copy(array $config, string $path): void {
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

    // Perform copy
    if (is_dir($path)) {
        recursive_copy($path, $dest_path);
    } else {
        copy($path, $dest_path);
    }

    http_response_code(201);
    header('Content-Length: 0');
}

/**
 * Recursively copy directory and all contents.
 *
 * @param string $source Source directory
 * @param string $dest Destination directory
 * @return void
 */
function recursive_copy(string $source, string $dest): void {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($items as $item) {
        $target = $dest . '/' . $item->getRelativePathname();

        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }
        } else {
            copy($item->getRealPath(), $target);
        }
    }
}
