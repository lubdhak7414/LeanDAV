<?php
/**
 * Management UI for WebDAV server.
 * 
 * Browser-based dashboard for file management.
 */

/**
 * Handle UI requests (browser-based management).
 * 
 * @param array $config Configuration array
 * @return void
 */
function handle_ui_request(array $config): void {
    $action = $_GET['action'] ?? null;
    $path = $_GET['path'] ?? '/';
    
    // Handle UI actions
    if ($action) {
        handle_ui_action($config, $action, $path);
        return;
    }
    
    // Render dashboard
    render_dashboard($config, $path);
}

/**
 * Handle UI actions (upload, download, mkdir, delete, rename).
 * 
 * @param array $config Configuration array
 * @param string $action Action to perform
 * @param string $path Resource path
 * @return void
 */
function handle_ui_action(array $config, string $action, string $path): void {
    // Validate path via traversal protection
    $storage = rtrim($config['storage_path'], '/');
    $resolved = resolve_path($path, $config['storage_path']);
    if ($resolved === false && $action !== 'mkdir') {
        http_response_code(403);
        echo 'Forbidden';
        return;
    }
    $full_path = $resolved !== false ? $resolved : $storage . '/' . ltrim($path, '/');
    
    switch ($action) {
        case 'download':
            // Stream file download
            if (!file_exists($full_path) || is_dir($full_path)) {
                http_response_code(404);
                echo 'File not found';
                return;
            }
            
            $mime = detect_mime($full_path);
            $size = filesize($full_path);
            
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . $size);
            $dl_name = rawurlencode(basename($full_path));
            header("Content-Disposition: attachment; filename*=UTF-8''{$dl_name}");
            header('X-Content-Type-Options: nosniff');
            
            readfile($full_path);
            break;
            
        case 'upload':
            // Handle multipart upload
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo 'Method not allowed';
                return;
            }
            
            if (!isset($_FILES['file'])) {
                http_response_code(400);
                echo 'No file uploaded';
                return;
            }
            
            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                http_response_code(500);
                echo 'Upload error: ' . $file['error'];
                return;
            }
            
            // Check file size
            if ($file['size'] > $config['max_upload_size']) {
                http_response_code(507);
                echo 'File too large';
                return;
            }
            
            // Append filename to path
            if (is_dir($full_path) || substr($full_path, -1) === '/') {
                $full_path = rtrim($full_path, '/') . '/' . $file['name'];
            }

            // Ensure target directory exists
            $target_dir = dirname($full_path);
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                http_response_code(201);
                echo 'File uploaded successfully';
            } else {
                http_response_code(500);
                echo 'Failed to move uploaded file';
            }
            break;
            
        case 'mkdir':
            // Create directory
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo 'Method not allowed';
                return;
            }
            
            if (file_exists($full_path)) {
                http_response_code(409);
                echo 'Directory already exists';
                return;
            }
            
            if (mkdir($full_path, 0755, true)) {
                http_response_code(201);
                echo 'Directory created';
            } else {
                http_response_code(500);
                echo 'Failed to create directory';
            }
            break;
            
        case 'delete':
            // Delete file or directory
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo 'Method not allowed';
                return;
            }
            
            if (!file_exists($full_path)) {
                http_response_code(404);
                echo 'File not found';
                return;
            }
            
            if (is_file($full_path)) {
                unlink($full_path);
            } else {
                recursive_delete($full_path);
            }
            
            http_response_code(204);
            break;
            
        case 'rename':
            // Rename file or directory
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo 'Method not allowed';
                return;
            }
            
            $new_name = basename($_POST['new_name'] ?? '');
            if (empty($new_name)) {
                http_response_code(400);
                echo 'New name required';
                return;
            }
            
            $new_path = dirname($full_path) . '/' . $new_name;
            if (file_exists($new_path)) {
                http_response_code(409);
                echo 'Target already exists';
                return;
            }
            
            if (rename($full_path, $new_path)) {
                http_response_code(200);
                echo 'Renamed successfully';
            } else {
                http_response_code(500);
                echo 'Failed to rename';
            }
            break;
            
        default:
            http_response_code(400);
            echo 'Unknown action';
            break;
    }
}

/**
 * Render the management dashboard.
 * 
 * @param array $config Configuration array
 * @param string $path Current directory path
 * @return void
 */
function render_dashboard(array $config, string $path): void {
    $storage = rtrim($config['storage_path'], '/');
    $full_path = $storage . '/' . ltrim($path, '/');
    
    // Ensure path exists
    if (!is_dir($full_path)) {
        $full_path = $storage;
        $path = '/';
    }
    
    // Scan directory
    $entries = scan_directory($full_path, $config['hide_dotfiles'] ?? true);
    
    // Calculate stats
    $total_files = 0;
    $total_size = 0;
    foreach ($entries as $entry) {
        $entry_path = $full_path . '/' . $entry;
        if (is_file($entry_path)) {
            $total_files++;
            $total_size += filesize($entry_path);
        }
    }
    
    // Build breadcrumb
    $breadcrumb = build_breadcrumb($path);
    
    // Get username for display
    $username = $config['auth']['username'] ?? 'admin';
    
    // Get max upload size in MB
    $max_upload_mb = round($config['max_upload_size'] / 1048576);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebDAV Server</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'JetBrains Mono', monospace;
            background: #0a0a0a;
            color: #e8e4dc;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #141414;
            border: 1px solid #2a2a2a;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: 700;
            color: #e8a530;
        }
        
        .header .user {
            font-size: 12px;
            color: #5a5550;
        }
        
        .breadcrumb {
            padding: 15px 20px;
            background: #141414;
            border: 1px solid #2a2a2a;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #e8a530;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb .separator {
            color: #5a5550;
            margin: 0 8px;
        }
        
        .main {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .main {
                grid-template-columns: 1fr;
            }
        }
        
        .sidebar {
            background: #141414;
            border: 1px solid #2a2a2a;
            padding: 20px;
        }
        
        .sidebar h2 {
            font-size: 14px;
            font-weight: 500;
            color: #5a5550;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .upload-zone {
            border: 2px dashed #2a2a2a;
            border-radius: 8px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }
        
        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: #e8a530;
            background: rgba(232, 165, 48, 0.05);
        }
        
        .upload-zone .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .upload-zone .text {
            font-size: 12px;
            color: #5a5550;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #e8a530;
            color: #0a0a0a;
            border: none;
            border-radius: 6px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            background: #d4942a;
        }
        
        .btn-secondary {
            background: transparent;
            border: 1px solid #2a2a2a;
            color: #e8e4dc;
        }
        
        .btn-secondary:hover {
            background: #1a1a1a;
        }
        
        .stats {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #2a2a2a;
            font-size: 12px;
            color: #5a5550;
        }
        
        .stats .value {
            color: #e8e4dc;
            font-weight: 500;
        }
        
        .content {
            background: #141414;
            border: 1px solid #2a2a2a;
        }
        
        .content-header {
            display: grid;
            grid-template-columns: 1fr 100px 150px 100px;
            padding: 15px 20px;
            border-bottom: 1px solid #2a2a2a;
            font-size: 11px;
            color: #5a5550;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .file-list {
            min-height: 400px;
        }
        
        .file-item {
            display: grid;
            grid-template-columns: 1fr 100px 150px 100px;
            padding: 12px 20px;
            border-bottom: 1px solid #1a1a1a;
            font-size: 13px;
            transition: background 0.1s;
            cursor: pointer;
        }
        
        .file-item:hover {
            background: #1a1a1a;
        }
        
        .file-item .name {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-item .icon {
            font-size: 16px;
        }
        
        .file-item .size,
        .file-item .modified {
            color: #5a5550;
        }
        
        .file-item .actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        
        .file-item .actions button {
            background: transparent;
            border: none;
            color: #5a5550;
            cursor: pointer;
            padding: 4px;
            font-size: 14px;
        }
        
        .file-item .actions button:hover {
            color: #e8a530;
        }
        
        .file-item .actions button.delete:hover {
            color: #e85050;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            color: #5a5550;
            font-size: 14px;
        }
        
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: #141414;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }
        
        .modal-content h3 {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .modal-content input {
            width: 100%;
            padding: 12px;
            background: #0a0a0a;
            border: 1px solid #2a2a2a;
            border-radius: 6px;
            color: #e8e4dc;
            font-family: inherit;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .modal-content input:focus {
            outline: none;
            border-color: #e8a530;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .modal-actions .btn {
            width: auto;
            padding: 10px 20px;
        }
        
        /* Progress */
        .progress {
            display: none;
            margin-top: 15px;
        }
        
        .progress.active {
            display: block;
        }
        
        .progress-bar {
            height: 4px;
            background: #2a2a2a;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: #e8a530;
            transition: width 0.2s;
        }
        
        .progress-text {
            font-size: 11px;
            color: #5a5550;
            margin-top: 8px;
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #141414;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 15px 20px;
            font-size: 13px;
            z-index: 2000;
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
        }
        
        .toast.active {
            transform: translateY(0);
            opacity: 1;
        }
        
        .toast.success {
            border-color: #4caf50;
        }
        
        .toast.error {
            border-color: #e85050;
        }
        
        #fileInput {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WebDAV Server</h1>
            <span class="user">user: <?php echo htmlspecialchars($username); ?></span>
        </div>
        
        <div class="breadcrumb">
            <?php echo $breadcrumb; ?>
        </div>
        
        <div class="main">
            <div class="sidebar">
                <h2>Actions</h2>
                
                <div class="upload-zone" id="uploadZone">
                    <div class="icon">📁</div>
                    <div class="text">Drag & drop files here<br>or click to select</div>
                </div>
                
                <input type="file" id="fileInput" multiple>
                
                <button class="btn" onclick="document.getElementById('fileInput').click()">
                    Select Files
                </button>
                
                <button class="btn btn-secondary" onclick="showNewFolderModal()">
                    + New Folder
                </button>
                
                <div class="progress" id="progress">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText">Uploading...</div>
                </div>
                
                <div class="stats">
                    <div>Files: <span class="value"><?php echo $total_files; ?></span></div>
                    <div>Size: <span class="value"><?php echo format_size($total_size); ?></span></div>
                </div>
            </div>
            
            <div class="content">
                <div class="content-header">
                    <span>Name</span>
                    <span>Size</span>
                    <span>Modified</span>
                    <span>Actions</span>
                </div>
                
                <div class="file-list" id="fileList">
                    <?php if (empty($entries)): ?>
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <div>No files yet</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                            <?php
                            $entry_path = $full_path . '/' . $entry;
                            $is_dir = is_dir($entry_path);
                            $entry_url = rtrim($path, '/') . '/' . rawurlencode($entry);
                            if ($is_dir) $entry_url .= '/';
                            ?>
                            <div class="file-item" data-path="<?php echo htmlspecialchars($entry_url); ?>" data-type="<?php echo $is_dir ? 'dir' : 'file'; ?>">
                                <div class="name">
                                    <span class="icon"><?php echo $is_dir ? '📁' : '📄'; ?></span>
                                    <?php if ($is_dir): ?>
                                        <a href="?path=<?php echo htmlspecialchars(rtrim($path, '/') . '/' . $entry); ?>"><?php echo htmlspecialchars($entry); ?></a>
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($entry); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="size"><?php echo $is_dir ? '—' : format_size(filesize($entry_path)); ?></div>
                                <div class="modified"><?php echo date('M d, Y', filemtime($entry_path)); ?></div>
                                <div class="actions">
                                    <?php if (!$is_dir): ?>
                                        <button onclick="downloadFile('<?php echo htmlspecialchars($entry_url); ?>')" title="Download">⬇</button>
                                    <?php endif; ?>
                                    <button onclick="showRenameModal('<?php echo htmlspecialchars($entry); ?>', '<?php echo htmlspecialchars($entry_url); ?>')" title="Rename">✏️</button>
                                    <button class="delete" onclick="deleteItem('<?php echo htmlspecialchars($entry_url); ?>', '<?php echo htmlspecialchars($entry); ?>')" title="Delete">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Folder Modal -->
    <div class="modal" id="newFolderModal">
        <div class="modal-content">
            <h3>New Folder</h3>
            <input type="text" id="newFolderName" placeholder="Folder name">
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('newFolderModal')">Cancel</button>
                <button class="btn" onclick="createFolder()">Create</button>
            </div>
        </div>
    </div>
    
    <!-- Rename Modal -->
    <div class="modal" id="renameModal">
        <div class="modal-content">
            <h3>Rename</h3>
            <input type="text" id="renameName" placeholder="New name">
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('renameModal')">Cancel</button>
                <button class="btn" onclick="renameItem()">Rename</button>
            </div>
        </div>
    </div>
    
    <!-- Toast -->
    <div class="toast" id="toast"></div>
    
    <script>
        // Current path
        const currentPath = '<?php echo htmlspecialchars($path); ?>';
        const maxUploadSize = <?php echo $config['max_upload_size']; ?>;
        
        // Upload zone
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        
        uploadZone.addEventListener('click', () => fileInput.click());
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
        
        // Handle file uploads
        async function handleFiles(files) {
            for (const file of files) {
                if (file.size > maxUploadSize) {
                    showToast('File too large: ' + file.name, 'error');
                    continue;
                }
                
                await uploadFile(file);
            }
        }
        
        async function uploadFile(file) {
            const progress = document.getElementById('progress');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            
            progress.classList.add('active');
            progressText.textContent = 'Uploading ' + file.name + '...';
            progressFill.style.width = '0%';
            
            const formData = new FormData();
            formData.append('file', file);
            
            try {
                const response = await fetch('?action=upload&path=' + encodeURIComponent(currentPath), {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    showToast('Uploaded: ' + file.name, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    const text = await response.text();
                    showToast('Upload failed: ' + text, 'error');
                }
            } catch (error) {
                showToast('Upload error: ' + error.message, 'error');
            }
            
            progress.classList.remove('active');
        }
        
        // Download file
        function downloadFile(path) {
            window.location.href = '?action=download&path=' + encodeURIComponent(path);
        }
        
        // Delete item
        async function deleteItem(path, name) {
            if (!confirm('Delete "' + name + '"?')) {
                return;
            }
            
            try {
                const response = await fetch('?action=delete&path=' + encodeURIComponent(path), {
                    method: 'POST'
                });
                
                if (response.ok) {
                    showToast('Deleted: ' + name, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    const text = await response.text();
                    showToast('Delete failed: ' + text, 'error');
                }
            } catch (error) {
                showToast('Delete error: ' + error.message, 'error');
            }
        }
        
        // New folder modal
        function showNewFolderModal() {
            document.getElementById('newFolderModal').classList.add('active');
            document.getElementById('newFolderName').focus();
        }
        
        async function createFolder() {
            const name = document.getElementById('newFolderName').value.trim();
            if (!name) {
                return;
            }
            
            const path = currentPath === '/' ? '/' + name : currentPath + '/' + name;
            
            try {
                const response = await fetch('?action=mkdir&path=' + encodeURIComponent(path), {
                    method: 'POST'
                });
                
                if (response.ok) {
                    showToast('Created folder: ' + name, 'success');
                    closeModal('newFolderModal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    const text = await response.text();
                    showToast('Create failed: ' + text, 'error');
                }
            } catch (error) {
                showToast('Create error: ' + error.message, 'error');
            }
        }
        
        // Rename modal
        let renamePath = '';
        
        function showRenameModal(currentName, path) {
            renamePath = path;
            document.getElementById('renameName').value = currentName;
            document.getElementById('renameModal').classList.add('active');
            document.getElementById('renameName').focus();
            document.getElementById('renameName').select();
        }
        
        async function renameItem() {
            const newName = document.getElementById('renameName').value.trim();
            if (!newName) {
                return;
            }
            
            try {
                const response = await fetch('?action=rename&path=' + encodeURIComponent(renamePath), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'new_name=' + encodeURIComponent(newName)
                });
                
                if (response.ok) {
                    showToast('Renamed successfully', 'success');
                    closeModal('renameModal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    const text = await response.text();
                    showToast('Rename failed: ' + text, 'error');
                }
            } catch (error) {
                showToast('Rename error: ' + error.message, 'error');
            }
        }
        
        // Modal helpers
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        // Toast notifications
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast active ' + type;
            
            setTimeout(() => {
                toast.classList.remove('active');
            }, 3000);
        }
        
        // Close modals on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
            }
        });
        
        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
<?php
}

/**
 * Build breadcrumb HTML.
 * 
 * @param string $path Current path
 * @return string HTML breadcrumb
 */
function build_breadcrumb(string $path): string {
    $parts = array_filter(explode('/', $path));
    $html = '<a href="?path=/">/</a>';
    
    $current = '';
    foreach ($parts as $part) {
        $current .= '/' . $part;
        $html .= '<span class="separator">›</span>';
        $html .= '<a href="?path=' . htmlspecialchars(rawurlencode($current)) . '">' . htmlspecialchars($part) . '</a>';
    }
    
    return $html;
}

/**
 * Format file size to human-readable string.
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted size
 */
function format_size(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 1) . ' ' . $units[$i];
}