<?php
/**
 * Centralized XML error responses for WebDAV.
 * 
 * Returns proper WebDAV error responses with XML bodies.
 */

/**
 * Send a WebDAV error response.
 * 
 * @param int $status HTTP status code
 * @param string $condition Optional DAV error condition
 * @return void
 */
function dav_error(int $status, string $condition = ''): void {
    http_response_code($status);
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="utf-8"?>';
    echo '<D:error xmlns:D="DAV:">';
    if ($condition) {
        echo "<D:{$condition}/>";
    }
    echo '</D:error>';
}