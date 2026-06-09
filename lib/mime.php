<?php
/**
 * MIME type detection for WebDAV server.
 * 
 * Detects MIME types using fileinfo extension and extension mapping.
 */

/**
 * Detect MIME type of a file.
 * 
 * @param string $filepath Path to file
 * @return string MIME type
 */
function detect_mime(string $filepath): string {
    // Try to use fileinfo extension first
    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($filepath);
        if ($mime && $mime !== 'application/octet-stream') {
            return $mime;
        }
    }
    
    // Fallback to extension-based detection
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    
    $map = [
        // Documents
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        
        // Images
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'bmp'  => 'image/bmp',
        
        // Video
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'mkv'  => 'video/x-matroska',
        'avi'  => 'video/x-msvideo',
        'mov'  => 'video/quicktime',
        'wmv'  => 'video/x-ms-wmv',
        
        // Audio
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'flac' => 'audio/flac',
        'aac'  => 'audio/aac',
        'ogg'  => 'audio/ogg',
        'wma'  => 'audio/x-ms-wma',
        
        // Archives
        'zip'  => 'application/zip',
        'rar'  => 'application/vnd.rar',
        '7z'   => 'application/x-7z-compressed',
        'tar'  => 'application/x-tar',
        'gz'   => 'application/gzip',
        'bz2'  => 'application/x-bzip2',
        
        // Text
        'txt'  => 'text/plain',
        'html' => 'text/html',
        'htm'  => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'csv'  => 'text/csv',
        'md'   => 'text/markdown',
        'php'  => 'text/x-php',
        'py'   => 'text/x-python',
        'java' => 'text/x-java',
        'c'    => 'text/x-c',
        'cpp'  => 'text/x-c++',
        'h'    => 'text/x-c',
        
        // Other
        'sql'  => 'application/sql',
        'log'  => 'text/plain',
        'ini'  => 'text/plain',
        'conf' => 'text/plain',
        'yml'  => 'text/yaml',
        'yaml' => 'text/yaml',
    ];
    
    return $map[$ext] ?? 'application/octet-stream';
}