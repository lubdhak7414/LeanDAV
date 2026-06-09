<?php
/**
 * Configuration file for WebDAV server.
 * 
 * Copy this file to config.php and customize.
 * config.php is gitignored and should never be committed.
 */

return [
    // Authentication credentials
    'auth' => [
        'username' => 'admin',
        'password' => 'change_me_please',
    ],
    
    // Storage path for user files (must be outside web root)
    'storage_path' => __DIR__ . '/data/',
    
    // Maximum upload size in bytes (100MB default)
    // Must stay in sync with php.ini upload_max_filesize + post_max_size
    'max_upload_size' => 104857600,
    
    // Lock directory and timeout
    'lock_dir' => __DIR__ . '/data/.locks/',
    'lock_timeout' => 600,  // Maps to WebDAV Timeout header format: Second-600
    
    // UI and logging settings
    'hide_dotfiles' => true,  // Affects both UI rendering AND PROPFIND response filtering
    'log_level' => 'info',   // 'debug' | 'info' | 'error' | 'none'
    'log_dir' => __DIR__ . '/data/.logs/',
    'max_log_size' => 10485760,  // 10MB — auto-rotate when exceeded
    
    // Depth infinity cap for PROPFIND requests
    'depth_infinity_cap' => 1000,  // Max entries when Depth: infinity is requested
];