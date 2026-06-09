<?php
/**
 * HTTP Range request parsing and 206 responses.
 * 
 * Enables resumable downloads, video seeking, and bandwidth reduction.
 */

/**
 * Parse Range header and send partial content response.
 * 
 * @param string $filepath Path to file
 * @param int $filesize File size in bytes
 * @return bool True if range was handled, false if no Range header
 */
function handle_range_request(string $filepath, int $filesize): bool {
    // Check for Range header
    $range_header = $_SERVER['HTTP_RANGE'] ?? '';
    if (empty($range_header)) {
        return false;
    }
    
    // Parse Range: bytes=start-end
    if (!preg_match('/^bytes=(\d+)-(\d*)$/', $range_header, $matches)) {
        // Invalid Range header
        http_response_code(416);
        header('Content-Range: bytes */' . $filesize);
        header('Content-Length: 0');
        return true;
    }
    
    $start = (int)$matches[1];
    $end = $matches[2] !== '' ? (int)$matches[2] : $filesize - 1;
    
    // Validate range
    if ($start >= $filesize) {
        // Range not satisfiable
        http_response_code(416);
        header('Content-Range: bytes */' . $filesize);
        header('Content-Length: 0');
        return true;
    }
    
    // Adjust end if it exceeds file size
    if ($end >= $filesize) {
        $end = $filesize - 1;
    }
    
    // Calculate content length
    $content_length = $end - $start + 1;
    
    // Set headers for partial content
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $filesize);
    header('Content-Length: ' . $content_length);
    header('Accept-Ranges: bytes');
    
    // Stream the requested range
    $fh = fopen($filepath, 'rb');
    if ($fh === false) {
        http_response_code(500);
        header('Content-Length: 0');
        return true;
    }
    
    fseek($fh, $start);
    
    $remaining = $content_length;
    while ($remaining > 0 && !feof($fh)) {
        $chunk_size = min(8192, $remaining);
        $chunk = fread($fh, $chunk_size);
        if ($chunk === false) {
            break;
        }
        echo $chunk;
        $remaining -= strlen($chunk);
        flush();
    }
    
    fclose($fh);
    return true;
}