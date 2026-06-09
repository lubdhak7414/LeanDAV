<?php
/**
 * PROPPATCH request handler.
 *
 * Dummy handler that echoes back properties.
 */

/**
 * Handle PROPPATCH request.
 *
 * @param array $config Configuration array
 * @param string $path Resolved filesystem path
 * @return void
 */
function handle_proppatch(array $config, string $path): void {
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
    $xml_writer->writeAttribute('xmlns:Z', 'urn:schemas-microsoft-com:');

    $xml_writer->startElementNS('D', 'response', null);

    // Href
    $storage = $config['storage_path'];
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
