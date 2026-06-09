<?php
/**
 * UI helper functions.
 *
 * Formatting and breadcrumb generation.
 */

/**
 * Format file size to human-readable string.
 *
 * @param int $bytes File size in bytes
 * @return string Formatted size
 */
function format_size(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;

    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, 1) . ' ' . $units[$i];
}

/**
 * Build breadcrumb HTML.
 *
 * @param string $path Current path
 * @return string HTML breadcrumb
 */
function build_breadcrumb(string $path): string {
    $parts = array_filter(explode('/', $path));
    $html = '<a href="?path=/">/</a>';

    $current = '';
    foreach ($parts as $part) {
        $current .= '/' . $part;
        $html .= '<span class="separator">›</span>';
        $html .= '<a href="?path=' . htmlspecialchars(rawurlencode($current)) . '">' . htmlspecialchars($part) . '</a>';
    }

    return $html;
}
