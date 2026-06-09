<?php
/**
 * LOCK/UNLOCK + garbage collection for WebDAV server.
 * 
 * File-based lock storage with JSON metadata.
 */

/**
 * Handle LOCK request.
 * 
 * @param array $config Configuration array
 * @param string $path Resource path
 * @return void
 */
function handle_lock(array $config, string $path): void {
    // Check if resource exists
    if (!file_exists($path)) {
        dav_error(404, 'Not Found');
        return;
    }
    
    // Parse XML body
    $body = file_get_contents('php://input');
    $xml = @simplexml_load_string($body);
    
    if ($xml === false) {
        dav_error(400, 'Bad Request');
        return;
    }
    
    // Extract lock scope and type
    $scope = 'exclusive';
    $type = 'write';
    
    $scope_elements = $xml->xpath('//D:lockscope/*');
    if ($scope_elements) {
        $scope = $scope_elements[0]->getName();
    }
    
    $type_elements = $xml->xpath('//D:locktype/*');
    if ($type_elements) {
        $type = $type_elements[0]->getName();
    }
    
    // Extract owner information
    $owner = '';
    $owner_elements = $xml->xpath('//D:owner/*');
    if ($owner_elements) {
        $owner = $owner_elements[0]->asXML();
    }
    
    // Check if resource is already locked
    $lock_dir = $config['lock_dir'];
    $lock_file = $lock_dir . md5($path) . '.lock';
    
    if (file_exists($lock_file)) {
        // Check if lock is expired
        $lock_data = json_decode(file_get_contents($lock_file), true);
        if ($lock_data && $lock_data['expires'] > time()) {
            // Check if this is a lock refresh (same token)
            $if_header = $_SERVER['HTTP_IF'] ?? '';
            if (str_contains($if_header, $lock_data['token'])) {
                // Refresh lock
                $lock_data['expires'] = time() + ($config['lock_timeout'] ?? 600);
                file_put_contents($lock_file, json_encode($lock_data), LOCK_EX);
                
                send_lock_response($lock_data);
                return;
            }
            
            dav_error(423, 'Locked');
            return;
        }
    }
    
    // Generate lock token
    $token = 'opaquelocktoken:' . sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    // Create lock data
    $lock_data = [
        'token' => $token,
        'path' => $path,
        'scope' => $scope,
        'type' => $type,
        'owner' => $owner,
        'created' => time(),
        'expires' => time() + ($config['lock_timeout'] ?? 600),
    ];
    
    // Ensure lock directory exists
    if (!is_dir($lock_dir)) {
        mkdir($lock_dir, 0755, true);
    }
    
    // Store lock
    file_put_contents($lock_file, json_encode($lock_data), LOCK_EX);
    
    // Send response
    send_lock_response($lock_data);
}

/**
 * Send LOCK response.
 * 
 * @param array $lock_data Lock metadata
 * @return void
 */
function send_lock_response(array $lock_data): void {
    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->startDocument('1.0', 'utf-8');
    $xml->startElementNS('D', 'prop', 'DAV:');
    
    $xml->startElementNS('D', 'lockdiscovery', null);
    $xml->startElementNS('D', 'activelock', null);
    
    $xml->startElementNS('D', 'lockscope', null);
    $xml->startElementNS('D', $lock_data['scope'], null);
    $xml->endElement();
    $xml->endElement();
    
    $xml->startElementNS('D', 'locktype', null);
    $xml->startElementNS('D', $lock_data['type'], null);
    $xml->endElement();
    $xml->endElement();
    
    $xml->startElementNS('D', 'lockroot', null);
    $xml->startElementNS('D', 'href', null);
    $xml->text($lock_data['path']);
    $xml->endElement();
    $xml->endElement();
    
    $xml->startElementNS('D', 'locktoken', null);
    $xml->startElementNS('D', 'href', null);
    $xml->text($lock_data['token']);
    $xml->endElement();
    $xml->endElement();
    
    $xml->startElementNS('D', 'timeout', null);
    $xml->text('Second-' . ($lock_data['expires'] - $lock_data['created']));
    $xml->endElement();
    
    if (!empty($lock_data['owner'])) {
        $xml->startElementNS('D', 'owner', null);
        $xml->raw($lock_data['owner']);
        $xml->endElement();
    }
    
    $xml->endElement(); // activelock
    $xml->endElement(); // lockdiscovery
    $xml->endElement(); // prop
    $xml->endDocument();
    
    http_response_code(200);
    header('Content-Type: application/xml; charset=utf-8');
    header('Lock-Token: ' . $lock_data['token']);
    header('Timeout: Second-' . ($lock_data['expires'] - $lock_data['created']));
    echo $xml->outputMemory();
}

/**
 * Handle UNLOCK request.
 * 
 * @param array $config Configuration array
 * @param string $path Resource path
 * @return void
 */
function handle_unlock(array $config, string $path): void {
    // Get Lock-Token header
    $lock_token = $_SERVER['HTTP_LOCK_TOKEN'] ?? '';
    if (empty($lock_token)) {
        dav_error(400, 'Bad Request');
        return;
    }
    
    $lock_dir = $config['lock_dir'];
    $lock_file = $lock_dir . md5($path) . '.lock';
    
    // Check if lock exists
    if (!file_exists($lock_file)) {
        dav_error(409, 'Conflict');
        return;
    }
    
    // Read lock data
    $lock_data = json_decode(file_get_contents($lock_file), true);
    
    // Verify token matches
    if ($lock_data['token'] !== $lock_token) {
        dav_error(409, 'Conflict');
        return;
    }
    
    // Delete lock
    unlink($lock_file);
    
    http_response_code(204);
    header('Content-Length: 0');
}

/**
 * Check if resource is locked.
 * 
 * @param string $path Resource path
 * @return bool True if locked
 */
function is_locked(string $path): bool {
    $lock_dir = dirname(__DIR__) . '/data/.locks/';
    $lock_file = $lock_dir . md5($path) . '.lock';
    
    if (!file_exists($lock_file)) {
        return false;
    }
    
    // Read lock data
    $lock_data = json_decode(file_get_contents($lock_file), true);
    
    // Check if lock is expired
    if ($lock_data && $lock_data['expires'] < time()) {
        unlink($lock_file);
        return false;
    }
    
    return true;
}

/**
 * Garbage collect expired locks.
 * 
 * @param string $lock_dir Lock directory
 * @return void
 */
function gc_locks(string $lock_dir): void {
    if (!is_dir($lock_dir)) {
        return;
    }
    
    $files = glob($lock_dir . '*.lock');
    if ($files === false) {
        return;
    }
    
    $now = time();
    
    foreach ($files as $file) {
        $lock_data = json_decode(file_get_contents($file), true);
        if ($lock_data && $lock_data['expires'] < $now) {
            unlink($file);
        }
    }
}

/**
 * Validate lock token from If header.
 * 
 * @param string $path Resource path
 * @param string $if_header If header value
 * @return bool True if lock token is valid
 */
function validate_lock_token(string $path, string $if_header): bool {
    if (empty($if_header)) {
        return false;
    }
    
    $lock_dir = dirname(__DIR__) . '/data/.locks/';
    $lock_file = $lock_dir . md5($path) . '.lock';
    
    if (!file_exists($lock_file)) {
        return false;
    }
    
    $lock_data = json_decode(file_get_contents($lock_file), true);
    if (!$lock_data) {
        return false;
    }
    
    // Check if lock is expired
    if ($lock_data['expires'] < time()) {
        unlink($lock_file);
        return false;
    }
    
    // Check if If header contains the lock token
    return str_contains($if_header, $lock_data['token']);
}