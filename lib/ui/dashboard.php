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
    $total_dirs = 0;
    $total_size = 0;
    $entry_meta = [];
    foreach ($entries_raw as $entry) {
        $entry_path = $full_path . '/' . $entry;
        $is_dir = is_dir($entry_path);
        $size = 0;
        $mtime = 0;
        if ($is_dir) {
            $total_dirs++;
        } else {
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
    $initial = strtoupper(mb_substr($username, 0, 1));

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <?php output_ui_css(); ?>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <h1>WebDAV Server</h1>
            </div>
            <div class="header-right">
                <div class="user-badge">
                    <div class="avatar"><?php echo $initial; ?></div>
                    <span class="username"><?php echo htmlspecialchars($username); ?></span>
                </div>
                <a class="header-icon" href="?action=logout" title="Logout" aria-label="Logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </a>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb-bar">
            <a class="up-btn" href="?path=/" title="Root" aria-label="Go to root">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            </a>
            <?php if ($path !== '/'): ?>
                <?php
                $parent = rtrim(dirname(rtrim($path, '/')), '/') ?: '/';
                ?>
                <a class="up-btn" href="?path=<?php echo rawurlencode($parent); ?>" title="Go up" aria-label="Go up one level">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                </a>
            <?php endif; ?>
            <?php echo $breadcrumb; ?>
        </div>

        <!-- Main Layout -->
        <div class="main">
            <!-- Sidebar -->
            <div class="sidebar">
                <h2>Upload</h2>

                <div class="upload-zone" id="uploadZone" role="button" tabindex="0">
                    <div class="upload-icon">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    </div>
                    <div class="text">Drop files here or click to browse</div>
                    <div class="hint">Max <?php echo $max_upload_mb; ?> MB per file</div>
                </div>

                <input type="file" id="fileInput" multiple>

                <div class="sidebar-buttons">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        Upload Files
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="showNewFolderModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                        New Folder
                    </button>
                </div>

                <div class="progress" id="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="File upload progress">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText">Uploading…</div>
                </div>

                <div class="stats">
                    <div class="stat-row"><span class="stat-icon">📁</span><span class="stat-label">Folders</span><span class="stat-value"><?php echo $total_dirs; ?></span></div>
                    <div class="stat-row"><span class="stat-icon">📄</span><span class="stat-label">Files</span><span class="stat-value"><?php echo $total_files; ?></span></div>
                    <div class="stat-row"><span class="stat-icon">💾</span><span class="stat-label">Total</span><span class="stat-value"><?php echo format_size($total_size); ?></span></div>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <!-- Toolbar: Filter + Bulk Actions -->
                <div class="toolbar">
                    <div class="toolbar-left">
                        <div class="search-box">
                            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <label for="searchInput" class="visually-hidden">Filter files by name</label>
                            <input type="text" id="searchInput" placeholder="Filter files…" autocomplete="off" aria-describedby="search-hint">
                            <div id="search-hint" class="visually-hidden">Type to filter the current folder</div>
                        </div>
                    </div>
                    <div class="toolbar-right">
                        <div class="bulk-actions" id="bulkActions">
                            <span class="selected-count" id="selectedCount">0 selected</span>
                            <button type="button" class="btn-bulk btn-bulk-download" onclick="bulkDownload()" title="Download selected as ZIP">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                ZIP
                            </button>
                            <button type="button" class="btn-bulk btn-bulk-delete" onclick="bulkDelete()" title="Delete selected">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- File Table -->
                <div class="content-header">
                    <label class="checkbox-cell header-cell">
                        <input type="checkbox" id="selectAll" title="Select all" aria-label="Select all files">
                    </label>
                    <span class="col-name sortable" data-sort="name" onclick="sortByColumn('name')">
                        Name
                        <span class="sort-indicator" id="sort-indicator-name">▲</span>
                    </span>
                    <span class="col-size sortable" data-sort="size" onclick="sortByColumn('size')">
                        Size
                        <span class="sort-indicator" id="sort-indicator-size"></span>
                    </span>
                    <span class="col-modified sortable" data-sort="date" onclick="sortByColumn('date')">
                        Modified
                        <span class="sort-indicator" id="sort-indicator-date"></span>
                    </span>
                    <span class="col-actions">Actions</span>
                </div>

                <div class="file-list" id="fileList">
                    <?php if (empty($entry_meta)): ?>
                        <div class="empty-state" id="emptyState">
                            <div class="empty-icon">
                                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                            <div class="empty-title">No files here</div>
                            <div class="empty-subtitle">Drop files or click Upload to get started</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($entry_meta as $meta): ?>
                            <?php
                            $entry = $meta['name'];
                            $is_dir = $meta['is_dir'];
                            $entry_url = rtrim($path, '/') . '/' . rawurlencode($entry);
                            if ($is_dir) $entry_url .= '/';
                            $is_zip = !$is_dir && in_array($meta['ext'], ['zip', 'gz', 'tar', 'bz2', '7z']);
                            ?>
                            <div class="file-item"
                                 data-path="<?php echo htmlspecialchars($entry_url); ?>"
                                 data-type="<?php echo $is_dir ? 'dir' : 'file'; ?>"
                                 data-name="<?php echo htmlspecialchars(strtolower($entry)); ?>"
                                 data-size="<?php echo $meta['size']; ?>"
                                 data-mtime="<?php echo $meta['mtime']; ?>"
                                 data-search="<?php echo htmlspecialchars(strtolower($entry)); ?>"
                                 data-origname="<?php echo htmlspecialchars($entry); ?>"
                                 data-zip="<?php echo $is_zip ? 'true' : 'false'; ?>">
                                <label class="checkbox-cell row-cell">
                                    <input type="checkbox" class="file-checkbox" value="<?php echo htmlspecialchars($entry_url); ?>" aria-label="Select <?php echo htmlspecialchars($entry); ?>">
                                </label>
                                <div class="name col-name">
                                    <?php if ($is_dir): ?>
                                        <span class="file-icon dir-icon">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                                        </span>
                                        <a href="?path=<?php echo rawurlencode(rtrim($path, '/') . '/' . $entry); ?>"><?php echo htmlspecialchars($entry); ?></a>
                                    <?php else: ?>
                                        <span class="file-icon"><?php echo file_type_icon_svg($meta['ext']); ?></span>
                                        <span class="name-text"><?php echo htmlspecialchars($entry); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="size col-size mono-data"><?php echo $is_dir ? '—' : format_size($meta['size']); ?></div>
                                <div class="modified col-modified mono-data"><?php echo date('M d, Y', $meta['mtime']); ?></div>
                                <div class="actions col-actions">
                                    <?php if (!$is_dir): ?>
                                        <button type="button" data-action="download" aria-label="Download <?php echo htmlspecialchars($entry); ?>" title="Download">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($is_zip): ?>
                                        <button type="button" data-action="unzip" aria-label="Extract <?php echo htmlspecialchars($entry); ?>" title="Extract archive">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" data-action="rename" aria-label="Rename <?php echo htmlspecialchars($entry); ?>" title="Rename">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                    </button>
                                    <button type="button" data-action="delete" class="delete" aria-label="Delete <?php echo htmlspecialchars($entry); ?>" title="Delete">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
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
            <h2 id="modal-newfolder-title">Create New Folder</h2>
            <label for="newFolderName" class="visually-hidden">Folder name</label>
            <input type="text" id="newFolderName" placeholder="Folder name" autocomplete="off">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('newFolderModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createFolder()">Create</button>
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
                <button type="button" class="btn btn-primary" onclick="renameItem()">Rename</button>
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