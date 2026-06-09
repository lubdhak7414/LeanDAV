<?php
/**
 * WebDAV protocol engine.
 * 
 * Handles all WebDAV methods: OPTIONS, PROPFIND, PROPPATCH, GET, HEAD, PUT, DELETE, MKCOL, MOVE, COPY.
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
    
    // Log incoming request
    log_request($method, $uri, 0);
    
    // Route by method
    switch ($method) {
        case 'OPTIONS':
            handle_options();
            break;
        case 'PROPFIND':
            handle_propfind($config);
            break;
        case 'PROPPATCH':
            handle_proppatch();
            break;
        case 'GET':
            handle_get($config);
            break;
        case 'HEAD':
            handle_head($config);
            break;
        case 'PUT':
            handle_put($config);
            break;
        case 'DELETE':
            handle_delete($config);
            break;
        case 'MKCOL':
            handle_mkcol($config);
            break;
        case 'MOVE':
            handle_move($config);
            break;
        case 'COPY':
            handle_copy($config);
            break;
        case 'LOCK':
            handle_lock($config, $uri);
            break;
        case 'UNLOCK':
            handle_unlock($config, $uri);
            break;
        default:
            http_response_code(405);
            header('Allow: OPTIONS, GET, HEAD, PUT, DELETE, MKCOL, COPY, MOVE, PROPFIND, PROPPATCH, LOCK, UNLOCK');
            echo '405 Method Not Allowed';
            break;
    }
}

/**
 * Handle OPTIONS request.
 * 
 * @return void
 */
function handle_options(): void {
    // TODO: Implement in Phase 6
    http_response_code(200);
    header('DAV: 1, 2');
    header('Allow: OPTIONS, GET, HEAD, PUT, DELETE, MKCOL, COPY, MOVE, PROPFIND, PROPPATCH, LOCK, UNLOCK');
    header('MS-Author-Via: DAV');
    header('Accept-Ranges: bytes');
    header('Content-Length: 0');
}