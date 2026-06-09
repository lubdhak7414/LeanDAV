<?php
/**
 * Structured request/response logging for WebDAV server.
 * 
 * Logs requests to file with auto-rotation.
 */

/**
 * Log a request/response.
 * 
 * @param string $method HTTP method
 * @param string $path Request path
 * @param int $status Response status code
 * @param string|null $error Optional error message
 * @return void
 */
function log_request(string $method, string $path, int $status, ?string $error = null): void {
    global $config;
    
    // Check log level
    $log_level = $config['log_level'] ?? 'info';
    if ($log_level === 'none') {
        return;
    }
    
    // Skip logging for certain methods in non-debug mode
    if ($log_level !== 'debug' && in_array($method, ['OPTIONS', 'PROPFIND'])) {
        return;
    }
    
    $log_dir = $config['log_dir'] ?? dirname(__DIR__) . '/data/.logs/';
    $max_log_size = $config['max_log_size'] ?? 10485760; // 10MB
    
    // Create log directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . 'webdav.log';
    
    // Auto-rotate if log file exceeds max size
    if (file_exists($log_file) && filesize($log_file) > $max_log_size) {
        $rotated = $log_file . '.' . date('Y-m-d-H-i-s');
        rename($log_file, $rotated);
    }
    
    // Format log entry
    $entry = sprintf("[%s] %s %s → %d%s\n",
        gmdate('Y-m-d H:i:s'),
        $method,
        $path,
        $status,
        $error ? " | {$error}" : ''
    );
    
    // Write to log file
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}