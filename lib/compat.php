<?php
/**
 * Client-specific compatibility layer.
 * 
 * Handles macOS quota skip, Windows trailing slash, Depth cap, temp file hiding.
 */

/**
 * Filter properties for macOS Finder compatibility.
 * 
 * @param array $properties Properties to filter
 * @return array Filtered properties
 */
function compat_filter_properties(array $properties): array {
    // If macOS Finder, remove quota-related properties
    if (is_macos_finder()) {
        $quota_props = ['quota-available-bytes', 'quota-used-bytes', 'quotaused'];
        $properties = array_diff($properties, $quota_props);
    }
    
    return $properties;
}

/**
 * Filter file listings for compatibility.
 * 
 * @param array $files File listings
 * @param bool $hide_dotfiles Whether to hide dotfiles
 * @return array Filtered listings
 */
function compat_filter_files(array $files, bool $hide_dotfiles = true): array {
    $filtered = [];
    
    foreach ($files as $file) {
        // Skip dotfiles if configured
        if ($hide_dotfiles && $file[0] === '.') {
            continue;
        }
        
        // Skip .part.* temp files (incomplete uploads)
        if (preg_match('/\.part\.[a-f0-9.]+$/', $file)) {
            continue;
        }
        
        $filtered[] = $file;
    }
    
    return $filtered;
}

/**
 * Apply client-specific compatibility fixes.
 * 
 * @return void
 */
function apply_compat_fixes(): void {
    // Windows MiniRedir trailing slash fix
    if (is_windows_miniredir()) {
        // Ensure trailing slash on collection URLs
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if ($uri !== '/' && substr($uri, -1) !== '/') {
            // Check if this is a directory request
            $path = resolve_path($uri, dirname(__DIR__) . '/data/');
            if ($path !== false && is_dir($path)) {
                header('Location: ' . $uri . '/');
                http_response_code(301);
                exit;
            }
        }
    }
}