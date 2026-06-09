<?php
/**
 * Input validation for UI actions.
 *
 * Upload error messages and name sanitization.
 */

/**
 * Human-readable upload error message.
 *
 * @param int $code PHP UPLOAD_ERR_* code
 * @return string Human-readable message
 */
function upload_error_message(int $code): string {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension',
    ];
    return $messages[$code] ?? "Upload error code: {$code}";
}

/**
 * Sanitize a rename/new-name string for safe filesystem use.
 *
 * @param string $name Raw name input
 * @return string|false Sanitized name, or false if invalid
 */
function sanitize_name(string $name): string|false {
    $name = trim($name);
    if ($name === '' || $name === '.' || $name === '..') {
        return false;
    }
    // Strip null bytes and path separators
    $name = str_replace(["\0", '/', '\\'], '', $name);
    // Strip Windows-reserved names (CON, PRN, AUX, NUL, COM1–COM9, LPT1–LPT9)
    $base = strtoupper(pathinfo($name, PATHINFO_FILENAME));
    if (in_array($base, ['CON','PRN','AUX','NUL','COM1','COM2','COM3','COM4','COM5','COM6','COM7','COM8','COM9','LPT1','LPT2','LPT3','LPT4','LPT5','LPT6','LPT7','LPT8','LPT9'], true)) {
        return false;
    }
    return $name;
}

/**
 * Resolve a $_GET['path'] value, decoding URL encoding.
 *
 * @param string $raw Raw GET path
 * @return string Decoded path
 */
function ui_resolve_get_path(string $raw): string {
    return rawurldecode($raw);
}
