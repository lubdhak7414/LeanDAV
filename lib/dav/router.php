<?php
/**
 * WebDAV request router.
 *
 * Routes incoming requests to the appropriate handler by HTTP method.
 */

/**
 * Handle WebDAV request routing.
 *
 * @param array $config Configuration array
 * @return void
 */
function handle_dav_request(array $config): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    // Resolve path
    $path = resolve_path($uri, $config['storage_path']);
    if ($path === false && $method !== 'OPTIONS') {
        dav_error(403, 'Forbidden');
        return;
    }

    // Log incoming request
    log_request($config, $method, $uri, 0);

    // Run garbage collection for locks on certain methods
    if (in_array($method, ['PROPFIND', 'OPTIONS', 'LOCK'])) {
        gc_locks($config['lock_dir']);
    }

    // Route by method
    switch ($method) {
        case 'OPTIONS':
            handle_options();
            break;
        case 'PROPFIND':
            handle_propfind($config, $path);
            break;
        case 'PROPPATCH':
            handle_proppatch($config, $path);
            break;
        case 'GET':
            handle_get($config, $path);
            break;
        case 'HEAD':
            handle_head($config, $path);
            break;
        case 'PUT':
            handle_put($config, $path);
            break;
        case 'DELETE':
            handle_delete($config, $path);
            break;
        case 'MKCOL':
            handle_mkcol($config, $path);
            break;
        case 'MOVE':
            handle_move($config, $path);
            break;
        case 'COPY':
            handle_copy($config, $path);
            break;
        case 'LOCK':
            handle_lock($config, $path);
            break;
        case 'UNLOCK':
            handle_unlock($config, $path);
            break;
        default:
            http_response_code(405);
            header('Allow: OPTIONS, GET, HEAD, PUT, DELETE, MKCOL, COPY, MOVE, PROPFIND, PROPPATCH, LOCK, UNLOCK');
            header('Content-Length: 0');
            echo '405 Method Not Allowed';
            break;
    }
}
