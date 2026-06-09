<?php
/**
 * Authentication middleware for WebDAV server.
 * 
 * Implements HTTP Basic Auth as specified in RFC 4918.
 */

/**
 * Require authentication for the request.
 * 
 * @param array $config Configuration array with auth credentials
 * @return void
 */
function require_auth(array $config): void {
    $username = $config['auth']['username'] ?? '';
    $password = $config['auth']['password'] ?? '';
    
    $provided_user = '';
    $provided_pass = '';
    
    // Method 1: Standard PHP_AUTH_USER/PW (works on most setups)
    if (!empty($_SERVER['PHP_AUTH_USER'])) {
        $provided_user = $_SERVER['PHP_AUTH_USER'];
        $provided_pass = $_SERVER['PHP_AUTH_PW'] ?? '';
    }
    // Method 2: Parse HTTP_AUTHORIZATION header (cPanel with SetEnvIf fix)
    elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/^Basic\s+(.+)$/i', $auth_header, $matches)) {
            $decoded = base64_decode($matches[1], true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$provided_user, $provided_pass] = explode(':', $decoded, 2);
            }
        }
    }
    // Method 3: REDIRECT_HTTP_AUTHORIZATION (some CGI/FPM setups)
    elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        if (preg_match('/^Basic\s+(.+)$/i', $auth_header, $matches)) {
            $decoded = base64_decode($matches[1], true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$provided_user, $provided_pass] = explode(':', $decoded, 2);
            }
        }
    }
    
    // Validate credentials
    if ($provided_user === $username && $provided_pass === $password) {
        return; // Auth successful
    }
    
    // Send 401 response with WWW-Authenticate header
    http_response_code(401);
    header('WWW-Authenticate: Basic realm="WebDAV Server"');
    header('Content-Type: text/plain; charset=utf-8');
    echo '401 Unauthorized';
    exit;
}
