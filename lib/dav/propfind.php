<?php
/**
 * PROPFIND request handler.
 *
 * Returns resource properties (allprop, propname, or specific properties).
 */

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

    generate_propfind_response($xml, $path, $href, $requested_props, $config);

    // If depth > 0, add children
    if ($depth > 0 && is_dir($path)) {
        $entries = scan_directory($path, $config['hide_dotfiles'] ?? true);
        $count = 0;
        $max_entries = ($depth === ($config['depth_infinity_cap'] ?? 1000)) ? $depth : PHP_INT_MAX;

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
    if ($stat === false) {
        dav_error(500, 'Internal Server Error');
        return;
    }

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
