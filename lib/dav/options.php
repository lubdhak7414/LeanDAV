<?php
/**
 * OPTIONS request handler.
 *
 * Returns server capabilities and supported methods.
 */

/**
 * Handle OPTIONS request.
 *
 * @return void
 */
function handle_options(): void {
    http_response_code(200);
    header('DAV: 1, 2');
    header('Allow: OPTIONS, GET, HEAD, PUT, DELETE, MKCOL, COPY, MOVE, PROPFIND, PROPPATCH, LOCK, UNLOCK');
    header('MS-Author-Via: DAV');
    header('Accept-Ranges: bytes');
    header('Content-Length: 0');
}
