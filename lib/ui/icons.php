<?php
/**
 * File type icons for UI.
 *
 * Maps file extensions to unique SVG icons for display.
 */

/**
 * Map a file extension to an inline SVG icon.
 *
 * @param string $ext File extension (lowercase)
 * @return string SVG icon markup
 */
function file_type_icon_svg(string $ext): string {
    // ===== ARCHIVES =====
    $archive_exts = ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'zst'];
    if (in_array($ext, $archive_exts)) {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#fbbf24" fill-opacity="0.18" stroke="#fbbf24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M21 8v13H3V8"></path>'
            . '<path d="M1 3h22v5H1z"></path>'
            . '<path d="M10 12h1"></path>'
            . '<path d="M13 12h1"></path>'
            . '<path d="M10 15h1"></path>'
            . '<path d="M13 15h1"></path>'
            . '<path d="M10 18h1"></path>'
            . '<path d="M13 18h1"></path>'
            . '<rect x="9" y="1" width="6" height="5" rx="1" fill="#fbbf24" fill-opacity="0.25" stroke="#fbbf24" stroke-width="1.5"></rect>'
            . '</svg>';
    }

    // ===== IMAGES =====
    $image_exts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico'];
    if (in_array($ext, $image_exts)) {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#f472b6" fill-opacity="0.15" stroke="#f472b6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>'
            . '<circle cx="8.5" cy="8.5" r="1.5"></circle>'
            . '<polyline points="21 15 16 10 5 21"></polyline>'
            . '</svg>';
    }

    // ===== VIDEO =====
    $video_exts = ['mp4', 'webm', 'mkv', 'avi', 'mov', 'wmv', 'flv'];
    if (in_array($ext, $video_exts)) {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#a78bfa" fill-opacity="0.15" stroke="#a78bfa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>'
            . '<line x1="7" y1="2" x2="7" y2="22"></line>'
            . '<line x1="17" y1="2" x2="17" y2="22"></line>'
            . '<line x1="2" y1="12" x2="22" y2="12"></line>'
            . '<line x1="2" y1="7" x2="7" y2="7"></line>'
            . '<line x1="2" y1="17" x2="7" y2="17"></line>'
            . '<line x1="17" y1="7" x2="22" y2="7"></line>'
            . '<line x1="17" y1="17" x2="22" y2="17"></line>'
            . '</svg>';
    }

    // ===== AUDIO =====
    $audio_exts = ['mp3', 'wav', 'flac', 'aac', 'ogg', 'wma', 'm4a'];
    if (in_array($ext, $audio_exts)) {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#34d399" fill-opacity="0.15" stroke="#34d399" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M9 18V5l12-2v13"></path>'
            . '<circle cx="6" cy="18" r="3"></circle>'
            . '<circle cx="18" cy="16" r="3"></circle>'
            . '</svg>';
    }

    // ===== PDF =====
    if ($ext === 'pdf') {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#ef4444" fill-opacity="0.15" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>'
            . '<polyline points="14 2 14 8 20 8"></polyline>'
            . '<path d="M9 15v-2h2a1 1 0 1 1 0 2H9z"></path>'
            . '</svg>';
    }

    // ===== WORD DOCUMENTS =====
    $word_exts = ['doc', 'docx'];
    if (in_array($ext, $word_exts)) {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#3b82f6" fill-opacity="0.15" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>'
            . '<polyline points="14 2 14 8 20 8"></polyline>'
            . '<line x1="8" y1="13" x2="16" y2="13"></line>'
            . '<line x1="8" y1="17" x2="13" y2="17"></line>'
            . '</svg>';
    }

    // ===== EXCEL / SPREADSHEETS =====
    $excel_exts = ['xls', 'xlsx', 'csv'];
    if (in_array($ext, $excel_exts)) {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#22c55e" fill-opacity="0.15" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>'
            . '<polyline points="14 2 14 8 20 8"></polyline>'
            . '<line x1="8" y1="12" x2="16" y2="12"></line>'
            . '<line x1="8" y1="16" x2="16" y2="16"></line>'
            . '<line x1="12" y1="8" x2="12" y2="20"></line>'
            . '</svg>';
    }

    // ===== POWERPOINT =====
    $ppt_exts = ['ppt', 'pptx'];
    if (in_array($ext, $ppt_exts)) {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#f97316" fill-opacity="0.15" stroke="#f97316" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>'
            . '<polyline points="14 2 14 8 20 8"></polyline>'
            . '<rect x="8" y="12" width="8" height="6" rx="1"></rect>'
            . '</svg>';
    }

    // ===== CODE FILES =====
    $code_exts = ['php', 'js', 'ts', 'py', 'java', 'c', 'cpp', 'h', 'html', 'css', 'json', 'xml', 'yml', 'yaml', 'sql', 'rb', 'go', 'rs', 'swift', 'kt'];
    if (in_array($ext, $code_exts)) {
        $color_map = [
            'php'  => '#8b5cf6', 'js'   => '#eab308', 'ts'   => '#3b82f6', 'py'   => '#3b82f6',
            'java' => '#ef4444', 'c'    => '#6b7280', 'cpp'  => '#6b7280', 'h'    => '#6b7280',
            'html' => '#f97316', 'css'  => '#3b82f6', 'json' => '#eab308', 'xml'  => '#f97316',
            'yml'  => '#ef4444', 'yaml' => '#ef4444', 'sql'  => '#3b82f6', 'rb'   => '#ef4444',
            'go'   => '#3b82f6', 'rs'   => '#f97316', 'swift' => '#f97316', 'kt'   => '#8b5cf6',
        ];
        $color = $color_map[$ext] ?? '#8b5cf6';
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="' . $color . '" fill-opacity="0.15" stroke="' . $color . '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<polyline points="16 18 22 12 16 6"></polyline>'
            . '<polyline points="8 6 2 12 8 18"></polyline>'
            . '<line x1="14" y1="4" x2="10" y2="20"></line>'
            . '</svg>';
    }

    // ===== MARKDOWN =====
    if ($ext === 'md') {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#6b7280" fill-opacity="0.15" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>'
            . '<polyline points="14 2 14 8 20 8"></polyline>'
            . '<path d="M7 15V9l2.5 2.5L12 9v6"></path>'
            . '<path d="M14 15l2-2.5 2 2.5"></path>'
            . '</svg>';
    }

    // ===== TEXT / CONFIG =====
    $text_exts = ['txt', 'log', 'ini', 'conf', 'cfg', 'env', 'gitignore'];
    if (in_array($ext, $text_exts)) {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#9ca3af" fill-opacity="0.15" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>'
            . '<polyline points="14 2 14 8 20 8"></polyline>'
            . '<line x1="8" y1="13" x2="16" y2="13"></line>'
            . '<line x1="8" y1="17" x2="16" y2="17"></line>'
            . '<line x1="8" y1="9" x2="10" y2="9"></line>'
            . '</svg>';
    }

    // ===== DEFAULT GENERIC FILE =====
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="#9ca3af" fill-opacity="0.15" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
        . '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>'
        . '<polyline points="13 2 13 9 20 9"></polyline>'
        . '</svg>';
}
