<?php
/**
 * CSRF token management for UI.
 *
 * Generates and validates tokens for form protection.
 */

/**
 * Generate a CSRF token for form/request protection.
 *
 * @return string The token
 */
function ui_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['ui_csrf_token'])) {
        $_SESSION['ui_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ui_csrf_token'];
}

/**
 * Validate a CSRF token.
 *
 * @param string $token Token to validate
 * @return bool True if valid
 */
function ui_csrf_validate(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['ui_csrf_token']) && hash_equals($_SESSION['ui_csrf_token'], $token);
}
