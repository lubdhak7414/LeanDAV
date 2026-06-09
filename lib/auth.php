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
    
    // Check if credentials are provided
    $provided_user = $_SERVER['PHP_AUTH_USER'] ?? '';
    $provided_pass = $_SERVER['PHP_AUTH_PW'] ?? '';
    
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