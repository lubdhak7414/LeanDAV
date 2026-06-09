<?php
/**
 * Authentication middleware for WebDAV server.
 * 
 * Implements:
 * - Session-based auth for browser UI (custom login page)
 * - HTTP Basic Auth for WebDAV clients (RFC 4918)
 */

session_start();

/**
 * Check if user is authenticated via session.
 */
function is_session_auth(): bool {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

/**
 * Attempt to authenticate user with credentials.
 */
function attempt_login(string $username, string $password, array $config): bool {
    $valid_user = $config['auth']['username'] ?? '';
    $valid_pass = $config['auth']['password'] ?? '';
    
    if ($username === $valid_user && $password === $valid_pass) {
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

/**
 * Logout user (destroy session).
 */
function do_logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Detect if request is from a WebDAV client (not browser).
 */
function is_webdav_client(): bool {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // WebDAV methods always come from clients
    $dav_methods = ['PROPFIND', 'PROPPATCH', 'PUT', 'DELETE', 'MKCOL', 'MOVE', 'COPY', 'LOCK', 'UNLOCK'];
    if (in_array(strtoupper($method), $dav_methods)) {
        return true;
    }
    
    // Check for WebDAV client user agents
    $dav_clients = ['Microsoft-WebDAV', 'WebDAV', 'davfs', 'rclone', 'cyberduck', 'Mountain Duck'];
    foreach ($dav_clients as $client) {
        if (str_contains($user_agent, $client)) {
            return true;
        }
    }
    
    // If it's not a browser request, treat as WebDAV client
    if (!str_contains($accept, 'text/html') || !str_contains($user_agent, 'Mozilla')) {
        return true;
    }
    
    return false;
}

/**
 * Get credentials from Basic Auth header.
 */
function get_basic_auth_credentials(): ?array {
    // Method 1: Standard PHP_AUTH_USER/PW
    if (!empty($_SERVER['PHP_AUTH_USER'])) {
        return [
            'username' => $_SERVER['PHP_AUTH_USER'],
            'password' => $_SERVER['PHP_AUTH_PW'] ?? ''
        ];
    }
    
    // Method 2: Parse HTTP_AUTHORIZATION header
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/^Basic\s+(.+)$/i', $auth_header, $matches)) {
            $decoded = base64_decode($matches[1], true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$user, $pass] = explode(':', $decoded, 2);
                return ['username' => $user, 'password' => $pass];
            }
        }
    }
    
    // Method 3: REDIRECT_HTTP_AUTHORIZATION
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        if (preg_match('/^Basic\s+(.+)$/i', $auth_header, $matches)) {
            $decoded = base64_decode($matches[1], true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$user, $pass] = explode(':', $decoded, 2);
                return ['username' => $user, 'password' => $pass];
            }
        }
    }
    
    return null;
}

/**
 * Send Basic Auth challenge (for WebDAV clients).
 */
function send_basic_auth_challenge(): void {
    http_response_code(401);
    header('WWW-Authenticate: Basic realm="WebDAV Server"');
    header('Content-Type: text/plain; charset=utf-8');
    echo '401 Unauthorized';
    exit;
}

/**
 * Require authentication for the request.
 * 
 * - Browser requests: Use session-based auth (shows custom login page)
 * - WebDAV clients: Use Basic Auth (required for mounting)
 */
function require_auth(array $config): void {
    // Check if this is a WebDAV client
    if (is_webdav_client()) {
        // WebDAV clients must use Basic Auth
        $creds = get_basic_auth_credentials();
        if ($creds === null || 
            $creds['username'] !== ($config['auth']['username'] ?? '') || 
            $creds['password'] !== ($config['auth']['password'] ?? '')) {
            send_basic_auth_challenge();
        }
        return;
    }
    
    // Browser request - use session auth
    if (is_session_auth()) {
        return; // Already authenticated
    }
    
    // Check for login form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (attempt_login($username, $password, $config)) {
            // Redirect to remove POST data from URL
            header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
            exit;
        }
        
        // Login failed - show login page with error
        render_login_page($config, 'Invalid username or password');
        exit;
    }
    
    // Not authenticated - show login page
    render_login_page($config);
    exit;
}

/**
 * Render the login page.
 */
function render_login_page(array $config, string $error = ''): void {
    $username = $config['auth']['username'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — WebDAV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0c0c0e;
            color: #e4e4e7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .login-container {
            width: 100%;
            max-width: 380px;
            padding: 20px;
        }

        .login-card {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 16px;
            padding: 40px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: rgba(232, 165, 48, 0.12);
            border-radius: 14px;
            color: #e8a530;
            margin-bottom: 16px;
        }

        .login-header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #fafafa;
            margin-bottom: 6px;
        }

        .login-header p {
            font-size: 13px;
            color: #71717a;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 500;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            background: #0c0c0e;
            border: 1px solid #3f3f46;
            border-radius: 10px;
            color: #e4e4e7;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-group input::placeholder {
            color: #52525b;
        }

        .form-group input:focus {
            outline: none;
            border-color: #e8a530;
            box-shadow: 0 0 0 3px rgba(232, 165, 48, 0.12);
        }

        .login-btn {
            width: 100%;
            padding: 13px 20px;
            background: #e8a530;
            color: #0a0a0a;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }

        .login-btn:hover {
            background: #d4942a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(232, 165, 48, 0.25);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 12px 14px;
            color: #f87171;
            font-size: 13px;
            text-align: center;
        }

        .login-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 11px;
            color: #52525b;
        }

        .login-footer a {
            color: #71717a;
            text-decoration: none;
            transition: color 0.15s;
        }

        .login-footer a:hover {
            color: #e8a530;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <h1>WebDAV Server</h1>
                <p>Sign in to access your files</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" autocomplete="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
                </div>
                <button type="submit" name="login" value="1" class="login-btn">Sign In</button>
            </form>
        </div>
        <div class="login-footer">
            Secure connection required
        </div>
    </div>
</body>
</html>
<?php
}
