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
    // TODO: Implement in Phase 3
    return false;
}

/**
 * Check if client is macOS Finder.
 * 
 * @return bool
 */
function is_macos_finder(): bool {
    // TODO: Implement in Phase 3
    return false;
}

/**
 * Check if client is Windows MiniRedir.
 * 
 * @return bool
 */
function is_windows_miniredir(): bool {
    // TODO: Implement in Phase 3
    return false;
}

/**
 * Check if client is GVFS (Linux).
 * 
 * @return bool
 */
function is_gvfs(): bool {
    // TODO: Implement in Phase 3
    return false;
}

/**
 * XML escape wrapper.
 * 
 * @param string $str String to escape
 * @return string Escaped string
 */
function xml_escape(string $str): string {
    // TODO: Implement in Phase 3
    return htmlspecialchars($str, ENT_XML1, 'UTF-8');
}

/**
 * Load and validate configuration.
 * 
 * @return array Configuration array
 */
function load_config(): array {
    // TODO: Implement in Phase 3
    return [];
}