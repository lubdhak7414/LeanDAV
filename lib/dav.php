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
    
    // Resolve path
    $path = resolve_path($uri, $config['storage_path']);
    if ($path === false && $method !== 'OPTIONS') {
        dav_error(403, 'Forbidden');
        return;
    }
    
    // Log incoming request
    log_request($method, $uri, 0);
    
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
            handle_proppatch($path);
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

/**
 * Handle PROPFIND request.
 * 
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_propfind(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }
    
    // Get Depth header (default to 1)
    $depth = $_SERVER['HTTP_DEPTH'] ?? '1';
    if ($depth === 'infinity') {
        $depth = $config['depth_infinity_cap'] ?? 1000;
    } else {
        $depth = (int)$depth;
    }
    
    // Parse request body for property request
    $body = file_get_contents('php://input');
    $requested_props = parse_propfind_body($body);
    
    // Generate response
    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->startDocument('1.0', 'utf-8');
    $xml->startElementNS('D', 'multistatus', 'DAV:');
    
    // Add namespace for custom properties
    $xml->writeAttribute('xmlns:Z', 'urn:schemas-microsoft-com:');
    
    $storage = rtrim($config['storage_path'], '/');
    
    // Generate response for current resource
    if ($path === $storage) {
        // Root collection
        $href = '/';
    } else {
        $href = '/' . str_replace($storage . '/', '', $path);
        if (is_dir($path)) {
            $href .= '/';
        }
    }
    
    // URL-encode the href
    $href = str_replace('%2F', '/', rawurlencode($href));
    $href = str_replace('%2F', '/', $href); // Keep forward slashes unencoded
    
    generate_propfind_response($xml, $path, $href, $requested_props, $config);
    
    // If depth > 0, add children
    if ($depth > 0 && is_dir($path)) {
        $entries = scan_directory($path, $config['hide_dotfiles'] ?? true);
        $count = 0;
        $max_entries = ($depth === $config['depth_infinity_cap'] ?? 1000) ? $depth : PHP_INT_MAX;
        
        foreach ($entries as $entry) {
            if ($count >= $max_entries) {
                break;
            }
            
            $entry_path = rtrim($path, '/') . '/' . $entry;
            $entry_href = rtrim($href, '/') . '/' . rawurlencode($entry);
            
            if (is_dir($entry_path)) {
                $entry_href .= '/';
            }
            
            generate_propfind_response($xml, $entry_path, $entry_href, $requested_props, $config);
            $count++;
        }
    }
    
    $xml->endElement(); // multistatus
    $xml->endDocument();
    
    http_response_code(207);
    header('Content-Type: application/xml; charset=utf-8');
    echo $xml->outputMemory();
}

/**
 * Parse PROPFIND request body.
 * 
 * @param string $body Request body
 * @return array List of requested properties
 */
function parse_propfind_body(string $body): array {
    // Default to allprop if body is empty
    if (empty(trim($body))) {
        return ['allprop'];
    }
    
    // Try to parse XML
    $xml = @simplexml_load_string($body);
    if ($xml === false) {
        return ['allprop'];
    }
    
    $props = [];
    
    // Check for allprop
    if ($xml->xpath('//D:allprop')) {
        return ['allprop'];
    }
    
    // Check for propname
    if ($xml->xpath('//D:propname')) {
        return ['propname'];
    }
    
    // Check for specific properties
    $prop_elements = $xml->xpath('//D:prop/*');
    if ($prop_elements) {
        foreach ($prop_elements as $prop) {
            $name = $prop->getName();
            $namespace = $prop->getNamespaces(true);
            $ns = reset($namespace) ?: 'DAV:';
            $props[] = ['name' => $name, 'namespace' => $ns];
        }
    }
    
    return empty($props) ? ['allprop'] : $props;
}

/**
 * Generate PROPFIND response for a single resource.
 * 
 * @param XMLWriter $xml XMLWriter instance
 * @param string $path Filesystem path
 * @param string $href URL-encoded href
 * @param array $requested_props Requested properties
 * @param array $config Configuration array
 * @return void
 */
function generate_propfind_response(XMLWriter $xml, string $path, string $href, array $requested_props, array $config): void {
    $is_dir = is_dir($path);
    $stat = stat($path);
    
    $xml->startElementNS('D', 'response', null);
    
    // Href
    $xml->startElementNS('D', 'href', null);
    $xml->text($href);
    $xml->endElement();
    
    // Propstat
    $xml->startElementNS('D', 'propstat', null);
    $xml->startElementNS('D', 'prop', null);
    
    // Determine which properties to include
    $all_props = ($requested_props === ['allprop'] || $requested_props === ['propname']);
    
    // Resource type
    if ($all_props || has_prop($requested_props, 'resourcetype')) {
        $xml->startElementNS('D', 'resourcetype', null);
        if ($is_dir) {
            $xml->startElementNS('D', 'collection', null);
            $xml->endElement();
        }
        $xml->endElement();
    }
    
    // Display name
    if ($all_props || has_prop($requested_props, 'displayname')) {
        $name = basename($path);
        if ($path === rtrim($config['storage_path'], '/')) {
            $name = '/';
        }
        $xml->startElementNS('D', 'displayname', null);
        $xml->text($name);
        $xml->endElement();
    }
    
    // Content length (files only)
    if (!$is_dir && ($all_props || has_prop($requested_props, 'getcontentlength'))) {
        $xml->startElementNS('D', 'getcontentlength', null);
        $xml->text($stat['size']);
        $xml->endElement();
    }
    
    // Last modified
    if ($all_props || has_prop($requested_props, 'getlastmodified')) {
        $xml->startElementNS('D', 'getlastmodified', null);
        $xml->text(gmdate('D, d M Y H:i:s', $stat['mtime']) . ' GMT');
        $xml->endElement();
    }
    
    // Creation date
    if ($all_props || has_prop($requested_props, 'creationdate')) {
        $xml->startElementNS('D', 'creationdate', null);
        $xml->text(gmdate('Y-m-d\TH:i:s\Z', $stat['ctime']));
        $xml->endElement();
    }
    
    // Content type (files only)
    if (!$is_dir && ($all_props || has_prop($requested_props, 'getcontenttype'))) {
        $mime = detect_mime($path);
        $xml->startElementNS('D', 'getcontenttype', null);
        $xml->text($mime);
        $xml->endElement();
    }
    
    // ETag
    if ($all_props || has_prop($requested_props, 'getetag')) {
        $etag = sprintf('"%x-%x"', $stat['mtime'], $stat['size']);
        $xml->startElementNS('D', 'getetag', null);
        $xml->text($etag);
        $xml->endElement();
    }
    
    // Supported lock
    if ($all_props || has_prop($requested_props, 'supportedlock')) {
        $xml->startElementNS('D', 'supportedlock', null);
        $xml->startElementNS('D', 'lockentry', null);
        $xml->startElementNS('D', 'lockscope', null);
        $xml->startElementNS('D', 'exclusive', null);
        $xml->endElement();
        $xml->endElement();
        $xml->startElementNS('D', 'locktype', null);
        $xml->startElementNS('D', 'write', null);
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
    }
    
    // Lock discovery
    if ($all_props || has_prop($requested_props, 'lockdiscovery')) {
        $xml->startElementNS('D', 'lockdiscovery', null);
        // Lock info will be added here if resource is locked
        $xml->endElement();
    }
    
    $xml->endElement(); // prop
    $xml->startElementNS('D', 'status', null);
    $xml->text('HTTP/1.1 200 OK');
    $xml->endElement();
    $xml->endElement(); // propstat
    $xml->endElement(); // response
}

/**
 * Check if a property is in the requested list.
 * 
 * @param array $requested_props Requested properties
 * @param string $prop_name Property name to check
 * @return bool
 */
function has_prop(array $requested_props, string $prop_name): bool {
    foreach ($requested_props as $prop) {
        if (is_array($prop) && $prop['name'] === $prop_name) {
            return true;
        }
    }
    return false;
}

/**
 * Scan directory and return sorted entries.
 * 
 * @param string $path Directory path
 * @param bool $hide_dotfiles Whether to hide dotfiles
 * @return array Sorted list of filenames
 */
function scan_directory(string $path, bool $hide_dotfiles): array {
    $entries = scandir($path);
    if ($entries === false) {
        return [];
    }
    
    // Filter dotfiles if configured
    if ($hide_dotfiles) {
        $entries = array_filter($entries, function ($entry) {
            return $entry[0] !== '.';
        });
    }
    
    // Filter .part.* temp files (incomplete uploads)
    $entries = array_filter($entries, function ($entry) {
        return !preg_match('/\\.part\\.[a-f0-9.]+$/', $entry);
    });
    
    // Sort: directories first, then files, alphabetically
    usort($entries, function ($a, $b) use ($path) {
        $a_is_dir = is_dir($path . '/' . $a);
        $b_is_dir = is_dir($path . '/' . $b);
        
        if ($a_is_dir && !$b_is_dir) {
            return -1;
        }
        if (!$a_is_dir && $b_is_dir) {
            return 1;
        }
        
        return strcasecmp($a, $b);
    });
    
    return $entries;
}

/**
 * Handle PROPPATCH request (dummy handler).
 * 
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_proppatch(string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }
    
    // Get the request body to echo back properties
    $body = file_get_contents('php://input');
    
    // Parse the properties from the request
    $xml = @simplexml_load_string($body);
    $props = [];
    
    if ($xml !== false) {
        $set_elements = $xml->xpath('//D:set/D:prop/*');
        if ($set_elements) {
            foreach ($set_elements as $prop) {
                $props[] = $prop->getName();
            }
        }
    }
    
    // Generate dummy 207 response
    $xml_writer = new XMLWriter();
    $xml_writer->openMemory();
    $xml_writer->startDocument('1.0', 'utf-8');
    $xml_writer->startElementNS('D', 'multistatus', 'DAV:');
    
    $xml_writer->startElementNS('D', 'response', null);
    
    // Href
    $storage = dirname($path);
    if ($path === rtrim($storage, '/')) {
        $href = '/';
    } else {
        $href = '/' . str_replace(rtrim($storage, '/') . '/', '', $path);
    }
    $href = str_replace('%2F', '/', rawurlencode($href));
    
    $xml_writer->startElementNS('D', 'href', null);
    $xml_writer->text($href);
    $xml_writer->endElement();
    
    // Propstat
    $xml_writer->startElementNS('D', 'propstat', null);
    $xml_writer->startElementNS('D', 'prop', null);
    
    // Echo back properties
    foreach ($props as $prop) {
        $xml_writer->startElementNS('D', $prop, null);
        $xml_writer->endElement();
    }
    
    $xml_writer->endElement(); // prop
    $xml_writer->startElementNS('D', 'status', null);
    $xml_writer->text('HTTP/1.1 200 OK');
    $xml_writer->endElement();
    $xml_writer->endElement(); // propstat
    $xml_writer->endElement(); // response
    $xml_writer->endElement(); // multistatus
    $xml_writer->endDocument();
    
    http_response_code(207);
    header('Content-Type: application/xml; charset=utf-8');
    echo $xml_writer->outputMemory();
}

/**
 * Handle GET request.
 * 
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_get(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }
    
    // Check if it's a directory
    if (is_dir($path)) {
        dav_error(405, 'Method Not Allowed');
        return;
    }
    
    // Get file info
    $filesize = filesize($path);
    $mtime = filemtime($path);
    $mime = detect_mime($path);
    
    // Generate ETag
    $etag = sprintf('"%x-%x"', $mtime, $filesize);
    
    // Check If-None-Match header for conditional requests
    $if_none_match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($if_none_match === $etag) {
        http_response_code(304);
        header('Content-Length: 0');
        return;
    }
    
    // Set headers
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $filesize);
    header('Accept-Ranges: bytes');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('X-Content-Type-Options: nosniff');
    
    // Handle Range requests
    $range_handled = handle_range_request($path, $filesize);
    if ($range_handled) {
        return;
    }
    
    // Stream file
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        dav_error(500, 'Internal Server Error');
        return;
    }
    
    http_response_code(200);
    
    while (!feof($fh)) {
        echo fread($fh, 8192);
        flush();
    }
    
    fclose($fh);
}

/**
 * Handle HEAD request.
 * 
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_head(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }
    
    // Get file info
    $filesize = filesize($path);
    $mtime = filemtime($path);
    $mime = detect_mime($path);
    
    // Generate ETag
    $etag = sprintf('"%x-%x"', $mtime, $filesize);
    
    // Set headers
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $filesize);
    header('Accept-Ranges: bytes');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('X-Content-Type-Options: nosniff');
    
    http_response_code(200);
    header('Content-Length: 0');
}

/**
 * Handle PUT request.
 * 
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_put(array $config, string $path): void {
    // Check if resource is locked
    if (is_locked($path, $config['lock_dir'])) {
        // Check for valid If header
        $if_header = $_SERVER['HTTP_IF'] ?? '';
        if (empty($if_header)) {
            dav_error(423, 'Locked');
            return;
        }
        // TODO: Validate lock token in Phase 13
    }
    
    // Ensure parent directory exists
    $parent = dirname($path);
    if (!is_dir($parent)) {
        dav_error(409, 'Conflict');
        return;
    }
    
    // Create temporary file for atomic write
    $tmpfile = $path . '.part.' . uniqid('', true);
    
    // Register cleanup function for connection abort
    register_shutdown_function(function () use ($tmpfile) {
        if (connection_aborted() && file_exists($tmpfile)) {
            unlink($tmpfile);
        }
    });
    
    $in = fopen('php://input', 'rb');
    $out = fopen($tmpfile, 'wb');
    
    if ($in === false || $out === false) {
        dav_error(500, 'Internal Server Error');
        return;
    }
    
    // Streaming byte counter
    $max = $config['max_upload_size'];
    $written = 0;
    
    while (!feof($in)) {
        $chunk = fread($in, 8192);
        $written += strlen($chunk);
        
        if ($written > $max) {
            fclose($in);
            fclose($out);
            unlink($tmpfile);
            http_response_code(507); // Insufficient Storage
            header('Content-Length: 0');
            return;
        }
        
        fwrite($out, $chunk);
    }
    
    fclose($in);
    fclose($out);
    
    // Atomic rename
    $existed = file_exists($path);
    rename($tmpfile, $path);
    
    http_response_code($existed ? 204 : 201);
    header('Content-Length: 0');
}

/**
 * Handle DELETE request.
 * 
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_delete(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }
    
    // Check if resource is locked
    if (is_locked($path, $config['lock_dir'])) {
        // Check for valid If header
        $if_header = $_SERVER['HTTP_IF'] ?? '';
        if (empty($if_header)) {
            dav_error(423, 'Locked');
            return;
        }
        // TODO: Validate lock token in Phase 13
    }
    
    // Recursive delete
    if (is_file($path)) {
        unlink($path);
    } else {
        recursive_delete($path);
    }
    
    http_response_code(204);
    header('Content-Length: 0');
}

/**
 * Recursively delete directory and all contents.
 * 
 * @param string $path Directory path
 * @return void
 */
function recursive_delete(string $path): void {
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getRealPath());
        } else {
            unlink($item->getRealPath());
        }
    }
    
    rmdir($path);
}

/**
 * Handle MKCOL request.
 * 
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_mkcol(array $config, string $path): void {
    // Check if resource already exists
    if (file_exists($path)) {
        dav_error(405, 'Method Not Allowed');
        return;
    }
    
    // Ensure parent directory exists
    $parent = dirname($path);
    if (!is_dir($parent)) {
        dav_error(409, 'Conflict');
        return;
    }
    
    // Create directory
    if (mkdir($path)) {
        http_response_code(201);
        header('Content-Length: 0');
    } else {
        dav_error(500, 'Internal Server Error');
    }
}

/**
 * Handle MOVE request.
 * 
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_move(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }
    
    // Get Destination header
    $destination = $_SERVER['HTTP_DESTINATION'] ?? '';
    if (empty($destination)) {
        dav_error(400, 'Bad Request');
        return;
    }
    
    // Resolve destination path
    $dest_path = resolve_path($destination, $config['storage_path']);
    if ($dest_path === false) {
        dav_error(403, 'Forbidden');
        return;
    }
    
    // Check if destination already exists
    $overwrite = $_SERVER['HTTP_OVERWRITE'] ?? 'T';
    if (file_exists($dest_path) && strtoupper($overwrite) !== 'T') {
        dav_error(412, 'Precondition Failed');
        return;
    }
    
    // Check if source is locked
    if (is_locked($path, $config['lock_dir'])) {
        $if_header = $_SERVER['HTTP_IF'] ?? '';
        if (empty($if_header)) {
            dav_error(423, 'Locked');
            return;
        }
    }
    
    // Check if destination is locked
    if (is_locked($dest_path, $config['lock_dir'])) {
        $if_header = $_SERVER['HTTP_IF'] ?? '';
        if (empty($if_header)) {
            dav_error(423, 'Locked');
            return;
        }
    }
    
    // Perform move
    if (rename($path, $dest_path)) {
        http_response_code(201);
        header('Content-Length: 0');
    } else {
        dav_error(500, 'Internal Server Error');
    }
}

/**
 * Handle COPY request.
 * 
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_copy(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }
    
    // Get Destination header
    $destination = $_SERVER['HTTP_DESTINATION'] ?? '';
    if (empty($destination)) {
        dav_error(400, 'Bad Request');
        return;
    }
    
    // Resolve destination path
    $dest_path = resolve_path($destination, $config['storage_path']);
    if ($dest_path === false) {
        dav_error(403, 'Forbidden');
        return;
    }
    
    // Check if destination already exists
    $overwrite = $_SERVER['HTTP_OVERWRITE'] ?? 'T';
    if (file_exists($dest_path) && strtoupper($overwrite) !== 'T') {
        dav_error(412, 'Precondition Failed');
        return;
    }
    
    // Perform copy
    if (is_dir($path)) {
        recursive_copy($path, $dest_path);
    } else {
        copy($path, $dest_path);
    }
    
    http_response_code(201);
    header('Content-Length: 0');
}

/**
 * Recursively copy directory and all contents.
 * 
 * @param string $source Source directory
 * @param string $dest Destination directory
 * @return void
 */
function recursive_copy(string $source, string $dest): void {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($items as $item) {
        $target = $dest . '/' . $item->getRelativePathname();
        
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }
        } else {
            copy($item->getRealPath(), $target);
        }
    }
}