<?php
/**
 * Shared utilities for WebDAV server.
 * 
 * Path sanitization, client detection, config loading.
 */

/**
 * Resolve path with traversal protection.
 * 
 * @param string $uri Request URI
 * @param string $storage Storage path
 * @return string|false Real path or false if path escapes storage
 */
function resolve_path(string $uri, string $storage) {
    // Remove query string and decode
    $path = parse_url($uri, PHP_URL_PATH);
    if ($path === false) {
        return false;
    }
    
    // Decode URL encoding
    $path = rawurldecode($path);
    
    // Remove leading slash
    $path = ltrim($path, '/');
    
    // Build full path
    $full_path = rtrim($storage, '/') . '/' . $path;
    
    // Normalize path (resolve . and ..)
    $full_path = realpath($full_path);
    
    // Check if path exists
    if ($full_path === false) {
        // For new resources, check if parent directory exists
        $parent = dirname($full_path);
        if (!is_dir($parent)) {
            return false;
        }
    }
    
    // Ensure path is within storage directory
    $storage_real = realpath($storage);
    if ($storage_real === false) {
        return false;
    }
    
    // Check if resolved path starts with storage path
    if (strpos($full_path, $storage_real) !== 0) {
        return false;
    }
    
    return $full_path;
}

/**
 * Check if client is macOS Finder.
 * 
 * @return bool
 */
function is_macos_finder(): bool {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return str_contains($ua, 'Darwin') || str_contains($ua, 'WebDAVFS');
}

/**
 * Check if client is Windows MiniRedir.
 * 
 * @return bool
 */
function is_windows_miniredir(): bool {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return str_contains($ua, 'Microsoft-WebDAV-MiniRedir');
}

/**
 * Check if client is GVFS (Linux).
 * 
 * @return bool
 */
function is_gvfs(): bool {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return str_contains($ua, 'GVFS');
}

/**
 * XML escape wrapper.
 * 
 * @param string $str String to escape
 * @return string Escaped string
 */
function xml_escape(string $str): string {
    return htmlspecialchars($str, ENT_XML1, 'UTF-8');
}

/**
 * Load and validate configuration.
 * 
 * @return array Configuration array
 */
function load_config(): array {
    $config_path = dirname(__DIR__) . '/config.php';
    
    if (!file_exists($config_path)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo '500 Server Error: Configuration file not found';
        exit;
    }
    
    $config = require $config_path;
    
    // Validate required keys
    $required_keys = ['auth', 'storage_path', 'max_upload_size', 'lock_dir', 'lock_timeout'];
    foreach ($required_keys as $key) {
        if (!isset($config[$key])) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo "500 Server Error: Missing required config key: {$key}";
            exit;
        }
    }
    
    // Validate auth credentials
    if (!isset($config['auth']['username']) || !isset($config['auth']['password'])) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo '500 Server Error: Missing auth credentials in config';
        exit;
    }
    
    return $config;
}