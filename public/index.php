<?php
/**
 * WebDAV Server Entry Point
 *
 * Single entry point for all WebDAV requests and management UI.
 * Routes requests based on HTTP method and client type.
 */

// Prevent partial file cleanup issues on connection abort
ignore_user_abort(true);

// Load configuration
$config_path = dirname(__DIR__) . '/config.php';
if (!file_exists($config_path)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo '500 Server Error: Configuration file not found';
    exit;
}
$config = require $config_path;

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Load shared libraries
require_once dirname(__DIR__) . '/lib/util.php';
require_once dirname(__DIR__) . '/lib/auth.php';
require_once dirname(__DIR__) . '/lib/log.php';
require_once dirname(__DIR__) . '/lib/error.php';
require_once dirname(__DIR__) . '/lib/mime.php';
require_once dirname(__DIR__) . '/lib/range.php';
require_once dirname(__DIR__) . '/lib/compat.php';
require_once dirname(__DIR__) . '/lib/lock.php';

// Load WebDAV handlers
require_once dirname(__DIR__) . '/lib/dav/router.php';
require_once dirname(__DIR__) . '/lib/dav/options.php';
require_once dirname(__DIR__) . '/lib/dav/propfind.php';
require_once dirname(__DIR__) . '/lib/dav/proppatch.php';
require_once dirname(__DIR__) . '/lib/dav/get.php';
require_once dirname(__DIR__) . '/lib/dav/head.php';
require_once dirname(__DIR__) . '/lib/dav/put.php';
require_once dirname(__DIR__) . '/lib/dav/delete.php';
require_once dirname(__DIR__) . '/lib/dav/mkcol.php';
require_once dirname(__DIR__) . '/lib/dav/move.php';
require_once dirname(__DIR__) . '/lib/dav/copy.php';
require_once dirname(__DIR__) . '/lib/dav/directory.php';

// Load UI modules
require_once dirname(__DIR__) . '/lib/ui/csrf.php';
require_once dirname(__DIR__) . '/lib/ui/validation.php';
require_once dirname(__DIR__) . '/lib/ui/icons.php';
require_once dirname(__DIR__) . '/lib/ui/helpers.php';
require_once dirname(__DIR__) . '/lib/ui/handler.php';
require_once dirname(__DIR__) . '/lib/ui/assets.php';
require_once dirname(__DIR__) . '/lib/ui/dashboard.php';

// Determine request type
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';

// Check if this is a browser request for the UI
$is_browser = (
    $request_method === 'GET' &&
    str_contains($accept, 'text/html') &&
    str_contains($user_agent, 'Mozilla')
);

// Check if this is a UI action (management operations)
$is_ui_action = (
    isset($_GET['action']) &&
    in_array($_GET['action'], ['download', 'upload', 'chunk-upload', 'mkdir', 'delete', 'rename', 'logout', 'zip-download', 'unzip'])
);

if ($is_browser || $is_ui_action) {
    // Require auth for browser UI too
    require_auth($config);
    // Handle management UI
    handle_ui_request($config);
} else {
    // Handle WebDAV request
    require_auth($config);
    handle_dav_request($config);
}
