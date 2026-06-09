<?php
/**
 * GET request handler.
 *
 * Serves file content with ETag, caching, and range support.
 */

/**
 * Handle GET request.
 *
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_get(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }

    // Check if it's a directory
    if (is_dir($path)) {
        dav_error(405, 'Method Not Allowed');
        return;
    }

    // Get file info
    $filesize = filesize($path);
    $mtime = filemtime($path);
    $mime = detect_mime($path);

    // Generate ETag
    $etag = sprintf('"%x-%x"', $mtime, $filesize);

    // Check If-None-Match header for conditional requests
    $if_none_match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($if_none_match === $etag) {
        http_response_code(304);
        header('Content-Length: 0');
        return;
    }

    // Set headers
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $filesize);
    header('Accept-Ranges: bytes');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('X-Content-Type-Options: nosniff');

    // Handle Range requests
    $range_handled = handle_range_request($path, $filesize);
    if ($range_handled) {
        return;
    }

    // Stream file
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        dav_error(500, 'Internal Server Error');
        return;
    }

    http_response_code(200);

    while (!feof($fh)) {
        echo fread($fh, 8192);
        flush();
    }

    fclose($fh);
}
