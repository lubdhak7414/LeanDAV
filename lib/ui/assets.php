<?php
/**
 * UI asset output helpers.
 *
 * Outputs CSS and JavaScript for the dashboard.
 */

/**
 * Output the dashboard CSS styles.
 *
 * @return void
 */
function output_ui_css(): void {
?>
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
            color: #a09b94;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
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
            color: #a09b94;
            margin: 0 4px;
        }

        .breadcrumb .up-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            color: #e8a530;
            text-decoration: none;
            font-size: 14px;
            margin-right: 8px;
            transition: background 0.15s, border-color 0.15s;
            flex-shrink: 0;
        }

        .breadcrumb .up-btn:hover {
            background: #222;
            border-color: #e8a530;
            text-decoration: none;
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

            .content-header {
                grid-template-columns: 1fr 80px 60px;
            }

            .file-item {
                grid-template-columns: 1fr 80px 60px;
            }

            .file-item .actions {
                position: static;
                opacity: 1;
            }

            .file-item .modified {
                display: none;
            }

            .content-header span:nth-child(3) {
                display: none;
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
            color: #a09b94;
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

        .upload-zone:focus {
            outline: 2px solid #e8a530;
            outline-offset: 2px;
        }

        .upload-zone .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .upload-zone .text {
            font-size: 12px;
            color: #a09b94;
        }

        .upload-zone .hint {
            font-size: 11px;
            color: #777;
            margin-top: 8px;
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

        .btn-danger {
            background: transparent;
            border: 1px solid #5a2020;
            color: #e85050;
        }

        .btn-danger:hover {
            background: #2a1010;
        }

        .stats {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #2a2a2a;
            font-size: 12px;
            color: #a09b94;
        }

        .stats .value {
            color: #e8e4dc;
            font-weight: 500;
        }

        /* Search / filter */
        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 12px;
            background: #0a0a0a;
            border: 1px solid #2a2a2a;
            border-radius: 6px;
            color: #e8e4dc;
            font-family: inherit;
            font-size: 12px;
            transition: border-color 0.2s;
        }

        .search-box input::placeholder {
            color: #777;
        }

        .search-box input:focus {
            outline: none;
            border-color: #e8a530;
        }

        /* Visually hidden for screen readers */
        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Sort controls */
        .sort-controls {
            display: flex;
            gap: 6px;
            margin-bottom: 12px;
        }

        .sort-btn {
            padding: 4px 10px;
            background: transparent;
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            color: #a09b94;
            font-family: inherit;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .sort-btn:hover {
            color: #e8e4dc;
            border-color: #a09b94;
        }

        .sort-btn.active {
            color: #e8a530;
            border-color: #e8a530;
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
            color: #a09b94;
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
        }

        .file-item[data-type="dir"] {
            cursor: pointer;
        }

        .file-item:hover {
            background: #1a1a1a;
        }

        .file-item .name {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow: hidden;
        }

        .file-item .name a,
        .file-item .name span.name-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-item .icon {
            font-size: 16px;
            flex-shrink: 0;
        }

        .file-item .size,
        .file-item .modified {
            color: #a09b94;
        }

        .file-item .actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .file-item:hover .actions,
        .file-item:focus-within .actions {
            opacity: 1;
        }

        .file-item .actions button {
            background: transparent;
            border: none;
            color: #a09b94;
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
            color: #a09b94;
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

        .modal-content h2 {
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
            width: 0%;
        }

        .progress-text {
            font-size: 11px;
            color: #a09b94;
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
<?php
}

/**
 * Output the dashboard JavaScript.
 *
 * @param string $path Current path
 * @param int $max_upload_size Max upload size in bytes
 * @param string $csrf_token CSRF token
 * @param int $max_upload_mb Max upload size in MB
 * @return void
 */
function output_ui_js(string $path, int $max_upload_size, string $csrf_token, int $max_upload_mb): void {
?>
    <script>
    "use strict";
    (() => {
        // Current path and CSRF token
        const currentPath = <?php echo json_encode($path); ?>;
        const maxUploadSize = <?php echo (int)$max_upload_size; ?>;
        const csrfToken = <?php echo json_encode($csrf_token); ?>;
        const uploadMaxMb = <?php echo (int)$max_upload_mb; ?>;

        // ===== Upload Zone =====
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');

        uploadZone.addEventListener('click', () => fileInput.click());
        uploadZone.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput.click();
            }
        });

        function setupDragZone(el) {
            el.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadZone.classList.add('dragover');
            });
            el.addEventListener('dragleave', (e) => {
                if (!el.contains(e.relatedTarget)) {
                    uploadZone.classList.remove('dragover');
                }
            });
            el.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
                if (e.dataTransfer.files.length > 0) {
                    handleFiles(e.dataTransfer.files);
                }
            });
        }

        setupDragZone(uploadZone);
        setupDragZone(fileList);

        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
            fileInput.value = '';
        });

        // ===== File Upload with Real Progress =====
        let uploadQueue = [];
        let uploadIndex = 0;
        let uploading = false;

        async function handleFiles(files) {
            for (const file of files) {
                if (file.size > maxUploadSize) {
                    showToast('File too large: ' + file.name + ' (' + formatBytes(file.size) + ' > ' + formatBytes(maxUploadSize) + ')', 'error');
                    continue;
                }
                uploadQueue.push(file);
            }
            if (!uploading) {
                processUploadQueue();
            }
        }

        async function processUploadQueue() {
            if (uploadIndex >= uploadQueue.length) {
                uploadQueue = [];
                uploadIndex = 0;
                uploading = false;
                return;
            }

            uploading = true;
            const total = uploadQueue.length;
            const file = uploadQueue[uploadIndex];
            uploadIndex++;

            await uploadFile(file, uploadIndex, total);

            await processUploadQueue();
        }

        function uploadFile(file, current, total) {
            return new Promise((resolve) => {
                const progress = document.getElementById('progress');
                const progressFill = document.getElementById('progressFill');
                const progressText = document.getElementById('progressText');

                progress.classList.add('active');
                progressText.textContent = 'Uploading ' + current + ' of ' + total + ': ' + file.name;
                progressFill.style.width = '0%';

                const xhr = new XMLHttpRequest();
                const formData = new FormData();
                formData.append('file', file);
                formData.append('csrf_token', csrfToken);

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        progressFill.style.width = pct + '%';
                        progress.setAttribute('aria-valuenow', pct);
                        progressText.textContent = 'Uploading ' + current + ' of ' + total + ': ' + file.name + ' (' + pct + '%)';
                    }
                });

                xhr.addEventListener('load', () => {
                    progress.classList.remove('active');
                    progress.setAttribute('aria-valuenow', '0');
                    if (xhr.status >= 200 && xhr.status < 300) {
                        showToast('Uploaded: ' + file.name, 'success');
                    } else {
                        showToast('Upload failed: ' + (xhr.responseText || xhr.statusText), 'error');
                    }
                    resolve();
                });

                xhr.addEventListener('error', () => {
                    progress.classList.remove('active');
                    progress.setAttribute('aria-valuenow', '0');
                    showToast('Upload error for: ' + file.name, 'error');
                    resolve();
                });

                xhr.open('POST', '?action=upload&path=' + encodeURIComponent(currentPath));
                xhr.send(formData);
            });
        }

        function formatBytes(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
            return Math.round(bytes * 10) / 10 + ' ' + units[i];
        }

        // ===== Download =====
        function downloadFile(path) {
            window.location.href = '?action=download&path=' + path;
        }

        // ===== Delete =====
        async function deleteItem(path, name) {
            if (!confirm('Delete "' + name + '"?')) {
                return;
            }

            try {
                const response = await fetch('?action=delete&path=' + encodeURIComponent(path), {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: 'csrf_token=' + encodeURIComponent(csrfToken)
                });

                if (response.ok) {
                    showToast('Deleted: ' + name, 'success');
                    // Remove the item from DOM instead of reloading
                    const item = fileList.querySelector('[data-path="' + CSS.escape(path) + '"]');
                    if (item) {
                        item.remove();
                    }
                    // Show empty state if no items left
                    if (fileList.querySelectorAll('.file-item').length === 0) {
                        fileList.innerHTML = '<div class="empty-state" id="emptyState"><div class="icon">📭</div><div>No files yet</div></div>';
                    }
                } else {
                    const text = await response.text();
                    showToast('Delete failed: ' + text, 'error');
                }
            } catch (error) {
                showToast('Delete error: ' + error.message, 'error');
            }
        }

        // ===== New Folder Modal =====
        let lastFocusedElement = null;

        function showNewFolderModal() {
            lastFocusedElement = document.activeElement;
            document.getElementById('newFolderModal').classList.add('active');
            const input = document.getElementById('newFolderName');
            input.value = '';
            input.focus();
        }

        async function createFolder() {
            const name = document.getElementById('newFolderName').value.trim();
            if (!name) {
                return;
            }

            const path = currentPath === '/' ? '/' + name : currentPath + '/' + name;

            try {
                const response = await fetch('?action=mkdir&path=' + encodeURIComponent(path), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'csrf_token=' + encodeURIComponent(csrfToken)
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

        // ===== Rename Modal =====
        let renamePath = '';

        function showRenameModal(currentName, path) {
            renamePath = path;
            lastFocusedElement = document.activeElement;
            const input = document.getElementById('renameName');
            input.value = currentName;
            document.getElementById('renameModal').classList.add('active');
            input.focus();
            input.select();
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
                    body: 'new_name=' + encodeURIComponent(newName) + '&csrf_token=' + encodeURIComponent(csrfToken)
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

        // ===== Modal Helpers =====
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            // Return focus to the element that triggered the modal
            if (lastFocusedElement) {
                lastFocusedElement.focus();
                lastFocusedElement = null;
            }
        }

        // Focus trap for modals
        function trapFocus(modal) {
            const focusable = modal.querySelectorAll('button, input, [tabindex]:not([tabindex="-1"])');
            if (focusable.length === 0) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            modal.addEventListener('keydown', (e) => {
                if (e.key !== 'Tab') return;

                if (e.shiftKey) {
                    if (document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    }
                } else {
                    if (document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            });
        }

        // Enter key submits the active modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                if (document.getElementById('newFolderModal').classList.contains('active')) {
                    e.preventDefault();
                    createFolder();
                } else if (document.getElementById('renameModal').classList.contains('active')) {
                    e.preventDefault();
                    renameItem();
                }
            }
        });

        // ===== Toast Notifications =====
        let toastTimer = null;
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast active ' + type;

            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.classList.remove('active');
            }, 3000);
        }

        // Close modals on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(m => {
                    closeModal(m.id);
                });
            }
        });

        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modal.id);
                }
            });
            // Set up focus trap
            trapFocus(modal);
        });

        // ===== Client-side Search / Filter =====
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase().trim();
            const items = fileList.querySelectorAll('.file-item');
            let visibleCount = 0;

            items.forEach(item => {
                const name = item.getAttribute('data-search') || '';
                const visible = !q || name.includes(q);
                item.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            // Show empty state if filtering hides all items
            let emptyState = document.getElementById('emptyState');
            if (visibleCount === 0 && q) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.id = 'emptyState';
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = '<div class="icon">🔍</div><div>No matching files</div>';
                    fileList.appendChild(emptyState);
                }
                emptyState.style.display = '';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }
        });

        // ===== Client-side Sorting =====
        let currentSort = 'name';
        let sortAsc = true;

        // Sort button base labels
        const sortLabels = { name: 'Name', size: 'Size', date: 'Date' };

        function sortFiles(field, btn) {
            if (currentSort === field) {
                sortAsc = !sortAsc;
            } else {
                currentSort = field;
                sortAsc = true;
            }

            // Reset all button texts to base labels, then update active one
            document.querySelectorAll('.sort-btn').forEach(b => {
                const sortField = b.getAttribute('data-sort');
                b.classList.remove('active');
                b.textContent = sortLabels[sortField] || sortField;
            });
            btn.classList.add('active');
            btn.textContent = sortLabels[field] + (sortAsc ? ' ▲' : ' ▼');

            const items = Array.from(fileList.querySelectorAll('.file-item'));

            items.sort((a, b) => {
                let va, vb;

                // Always keep directories first
                const aDir = a.getAttribute('data-type') === 'dir';
                const bDir = b.getAttribute('data-type') === 'dir';
                if (aDir && !bDir) return -1;
                if (!aDir && bDir) return 1;

                switch (field) {
                    case 'name':
                        va = a.getAttribute('data-name') || '';
                        vb = b.getAttribute('data-name') || '';
                        return sortAsc ? va.localeCompare(vb) : vb.localeCompare(va);
                    case 'size':
                        va = parseInt(a.getAttribute('data-size') || '0', 10);
                        vb = parseInt(b.getAttribute('data-size') || '0', 10);
                        return sortAsc ? va - vb : vb - va;
                    case 'date':
                        va = parseInt(a.getAttribute('data-mtime') || '0', 10);
                        vb = parseInt(b.getAttribute('data-mtime') || '0', 10);
                        return sortAsc ? va - vb : vb - va;
                    default:
                        return 0;
                }
            });

            // Also move any empty state element to the end
            const emptyState = document.getElementById('emptyState');
            items.forEach(item => fileList.appendChild(item));
            if (emptyState) fileList.appendChild(emptyState);
        }

        // Make functions globally accessible for onclick handlers
        window.downloadFile = downloadFile;
        window.deleteItem = deleteItem;
        window.showNewFolderModal = showNewFolderModal;
        window.createFolder = createFolder;
        window.showRenameModal = showRenameModal;
        window.renameItem = renameItem;
        window.closeModal = closeModal;
        window.sortFiles = sortFiles;
    })();
    </script>
<?php
}
