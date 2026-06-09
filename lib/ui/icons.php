<?php
/**
 * File type icons for UI.
 *
 * Maps file extensions to SVG icons for display.
 */

/**
 * Map a file extension to an inline SVG icon.
 *
 * @param string $ext File extension (lowercase)
 * @return string SVG icon markup
 */
function file_type_icon_svg(string $ext): string {
    $color_map = [
        // Images
        'png'  => '#f472b6', 'jpg'  => '#f472b6', 'jpeg' => '#f472b6', 'gif'  => '#f472b6',
        'webp' => '#f472b6', 'svg'  => '#f472b6', 'bmp'  => '#f472b6', 'ico'  => '#f472b6',
        // Video
        'mp4'  => '#a78bfa', 'webm' => '#a78bfa', 'mkv'  => '#a78bfa', 'avi'  => '#a78bfa',
        'mov'  => '#a78bfa', 'wmv'  => '#a78bfa',
        // Audio
        'mp3'  => '#34d399', 'wav'  => '#34d399', 'flac' => '#34d399', 'aac'  => '#34d399',
        'ogg'  => '#34d399', 'wma'  => '#34d399',
        // Archives
        'zip'  => '#fbbf24', 'rar'  => '#fbbf24', '7z'   => '#fbbf24', 'tar'  => '#fbbf24',
        'gz'   => '#fbbf24', 'bz2'  => '#fbbf24',
        // Documents
        'pdf'  => '#ef4444', 'doc'  => '#3b82f6', 'docx' => '#3b82f6', 'xls'  => '#22c55e',
        'xlsx' => '#22c55e', 'ppt'  => '#f97316', 'pptx' => '#f97316', 'csv'  => '#22c55e',
        // Code
        'php'  => '#8b5cf6', 'js'   => '#eab308', 'ts'   => '#3b82f6', 'py'   => '#3b82f6',
        'java' => '#ef4444', 'c'    => '#6b7280', 'cpp'  => '#6b7280', 'h'    => '#6b7280',
        'html' => '#f97316', 'css'  => '#3b82f6', 'json' => '#eab308', 'xml'  => '#f97316',
        'yml'  => '#ef4444', 'yaml' => '#ef4444', 'sql'  => '#3b82f6', 'md'   => '#6b7280',
        // Text / Config
        'txt'  => '#9ca3af', 'log'  => '#9ca3af', 'ini'  => '#9ca3af', 'conf' => '#9ca3af',
    ];

    $color = $color_map[$ext] ?? '#9ca3af';
    // Generic file icon SVG
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>';
}
