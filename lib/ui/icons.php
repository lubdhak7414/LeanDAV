<?php
/**
 * File type icons for UI.
 *
 * Maps file extensions to emoji display icons.
 */

/**
 * Map a file extension to a display emoji icon.
 *
 * @param string $ext File extension (lowercase)
 * @param bool $is_dir Whether entry is a directory
 * @return string Emoji icon
 */
function file_type_icon(string $ext, bool $is_dir): string {
    if ($is_dir) {
        return '📁';
    }
    $icon_map = [
        // Images
        'png'  => '🖼️', 'jpg'  => '🖼️', 'jpeg' => '🖼️', 'gif'  => '🖼️',
        'webp' => '🖼️', 'svg'  => '🖼️', 'bmp'  => '🖼️', 'ico'  => '🖼️',
        // Video
        'mp4'  => '🎬', 'webm' => '🎬', 'mkv'  => '🎬', 'avi'  => '🎬',
        'mov'  => '🎬', 'wmv'  => '🎬',
        // Audio
        'mp3'  => '🎵', 'wav'  => '🎵', 'flac' => '🎵', 'aac'  => '🎵',
        'ogg'  => '🎵', 'wma'  => '🎵',
        // Archives
        'zip'  => '📦', 'rar'  => '📦', '7z'   => '📦', 'tar'  => '📦',
        'gz'   => '📦', 'bz2'  => '📦',
        // Documents
        'pdf'  => '📕', 'doc'  => '📘', 'docx' => '📘', 'xls'  => '📗',
        'xlsx' => '📗', 'ppt'  => '📙', 'pptx' => '📙', 'csv'  => '📊',
        // Code
        'php'  => '🔧', 'js'   => '🔧', 'ts'   => '🔧', 'py'   => '🔧',
        'java' => '🔧', 'c'    => '🔧', 'cpp'  => '🔧', 'h'    => '🔧',
        'html' => '🔧', 'css'  => '🔧', 'json' => '🔧', 'xml'  => '🔧',
        'yml'  => '🔧', 'yaml' => '🔧', 'sql'  => '🔧', 'md'   => '🔧',
        // Text / Config
        'txt'  => '📄', 'log'  => '📄', 'ini'  => '📄', 'conf' => '📄',
    ];
    return $icon_map[$ext] ?? '📄';
}
