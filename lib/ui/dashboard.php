<?php
/**
 * Dashboard renderer.
 *
 * Builds the HTML dashboard for file management.
 */

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

    // Scan directory and compute stats in one pass
    $entries_raw = scan_directory($full_path, $config['hide_dotfiles'] ?? true);
    $total_files = 0;
    $total_size = 0;
    $entry_meta = [];  // pre-compute metadata for each entry
    foreach ($entries_raw as $entry) {
        $entry_path = $full_path . '/' . $entry;
        $is_dir = is_dir($entry_path);
        $size = 0;
        $mtime = 0;
        if (!$is_dir) {
            $total_files++;
            $size = @filesize($entry_path);
            if ($size === false) { $size = 0; }
            $total_size += $size;
        }
        $mtime = @filemtime($entry_path);
        if ($mtime === false) { $mtime = 0; }
        $ext = $is_dir ? '' : strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        $entry_meta[] = [
            'name'   => $entry,
            'is_dir' => $is_dir,
            'size'   => $size,
            'mtime'  => $mtime,
            'ext'    => $ext,
        ];
    }

    // Build breadcrumb
    $breadcrumb = build_breadcrumb($path);

    // Get username for display
    $username = $config['auth']['username'] ?? 'admin';

    // Get max upload size in MB
    $max_upload_mb = round($config['max_upload_size'] / 1048576);

    // CSRF token
    $csrf = ui_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(basename(rtrim($path, '/')) ?: 'WebDAV'); ?> — WebDAV</title>
    <meta name="description" content="WebDAV file manager for <?php echo htmlspecialchars($username); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <?php output_ui_css(); ?>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WebDAV Server</h1>
            <span class="user">user: <?php echo htmlspecialchars($username); ?></span>
        </div>

        <div class="breadcrumb">
            <?php
            // Up button (skip at root)
            if ($path !== '/') {
                $parent = rtrim(dirname(rtrim($path, '/')), '/') ?: '/';
                echo '<a class="up-btn" href="?path=' . rawurlencode($parent) . '" title="Go up">↑</a>';
            }
            ?>
            <?php echo $breadcrumb; ?>
        </div>

        <div class="main">
            <div class="sidebar">
                <h2>Actions</h2>

                <div class="search-box">
                    <label for="searchInput" class="visually-hidden">Filter files by name</label>
                    <input type="text" id="searchInput" placeholder="🔍  Filter files…" autocomplete="off" aria-describedby="search-hint">
                    <div id="search-hint" class="visually-hidden">Type to filter the current folder</div>
                </div>

                <div class="upload-zone" id="uploadZone" role="button" tabindex="0">
                    <div class="icon">📁</div>
                    <div class="text">Drag & drop files here<br>or click to select</div>
                    <div class="hint">Max <?php echo $max_upload_mb; ?> MB per file</div>
                </div>

                <input type="file" id="fileInput" multiple>

                <button type="button" class="btn" onclick="document.getElementById('fileInput').click()">
                    Select Files
                </button>

                <button type="button" class="btn btn-secondary" onclick="showNewFolderModal()">
                    + New Folder
                </button>

                <div class="progress" id="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="File upload progress">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText">Uploading…</div>
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

                <div class="sort-controls" style="padding: 10px 20px 0;">
                    <button class="sort-btn active" data-sort="name" onclick="sortFiles('name', this)">Name</button>
                    <button class="sort-btn" data-sort="size" onclick="sortFiles('size', this)">Size</button>
                    <button class="sort-btn" data-sort="date" onclick="sortFiles('date', this)">Date</button>
                </div>

                <div class="file-list" id="fileList">
                    <?php if (empty($entry_meta)): ?>
                        <div class="empty-state" id="emptyState">
                            <div class="icon">📭</div>
                            <div>No files yet</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($entry_meta as $meta): ?>
                            <?php
                            $entry = $meta['name'];
                            $is_dir = $meta['is_dir'];
                            $entry_url = rtrim($path, '/') . '/' . rawurlencode($entry);
                            if ($is_dir) $entry_url .= '/';
                            ?>
                            <div class="file-item"
                                 data-path="<?php echo htmlspecialchars($entry_url); ?>"
                                 data-type="<?php echo $is_dir ? 'dir' : 'file'; ?>"
                                 data-name="<?php echo htmlspecialchars(strtolower($entry)); ?>"
                                 data-size="<?php echo $meta['size']; ?>"
                                 data-mtime="<?php echo $meta['mtime']; ?>"
                                 data-search="<?php echo htmlspecialchars(strtolower($entry)); ?>">
                                <div class="name">
                                    <span class="icon"><?php echo file_type_icon($meta['ext'], $is_dir); ?></span>
                                    <?php if ($is_dir): ?>
                                        <a href="?path=<?php echo rawurlencode(rtrim($path, '/') . '/' . $entry); ?>"><?php echo htmlspecialchars($entry); ?></a>
                                    <?php else: ?>
                                        <span class="name-text"><?php echo htmlspecialchars($entry); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="size"><?php echo $is_dir ? '—' : format_size($meta['size']); ?></div>
                                <div class="modified"><?php echo date('M d, Y', $meta['mtime']); ?></div>
                                <div class="actions">
                                    <?php if (!$is_dir): ?>
                                        <button type="button" aria-label="Download <?php echo htmlspecialchars($entry); ?>" onclick="downloadFile('<?php echo htmlspecialchars(rawurlencode($entry_url)); ?>')"><span aria-hidden="true">⬇</span></button>
                                    <?php endif; ?>
                                    <button type="button" aria-label="Rename <?php echo htmlspecialchars($entry); ?>" onclick="showRenameModal(<?php echo json_encode($entry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>, '<?php echo htmlspecialchars(rawurlencode($entry_url)); ?>')"><span aria-hidden="true">✏️</span></button>
                                    <button type="button" class="delete" aria-label="Delete <?php echo htmlspecialchars($entry); ?>" onclick="deleteItem('<?php echo htmlspecialchars(rawurlencode($entry_url)); ?>', <?php echo json_encode($entry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)"><span aria-hidden="true">🗑️</span></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Folder Modal -->
    <div class="modal" id="newFolderModal" role="dialog" aria-modal="true" aria-labelledby="modal-newfolder-title">
        <div class="modal-content" role="document">
            <h2 id="modal-newfolder-title">New Folder</h2>
            <label for="newFolderName" class="visually-hidden">Folder name</label>
            <input type="text" id="newFolderName" placeholder="Folder name" autocomplete="off">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('newFolderModal')">Cancel</button>
                <button type="button" class="btn" onclick="createFolder()">Create</button>
            </div>
        </div>
    </div>

    <!-- Rename Modal -->
    <div class="modal" id="renameModal" role="dialog" aria-modal="true" aria-labelledby="modal-rename-title">
        <div class="modal-content" role="document">
            <h2 id="modal-rename-title">Rename</h2>
            <label for="renameName" class="visually-hidden">New name</label>
            <input type="text" id="renameName" placeholder="New name" autocomplete="off">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('renameModal')">Cancel</button>
                <button type="button" class="btn" onclick="renameItem()">Rename</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast" role="status" aria-live="polite" aria-atomic="true"></div>

    <?php output_ui_js($path, $config['max_upload_size'], $csrf, $max_upload_mb); ?>
</body>
</html>
<?php
}
