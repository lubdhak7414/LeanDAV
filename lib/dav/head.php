<?php
/**
 * HEAD request handler.
 *
 * Returns file metadata without content body.
 */

/**
 * Handle HEAD request.
 *
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_head(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }

    // Get file info
    $filesize = filesize($path);
    $mtime = filemtime($path);
    $mime = detect_mime($path);

    // Generate ETag
    $etag = sprintf('"%x-%x"', $mtime, $filesize);

    // Set headers
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $filesize);
    header('Accept-Ranges: bytes');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('X-Content-Type-Options: nosniff');

    http_response_code(200);
}
