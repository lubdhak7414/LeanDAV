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
        /* ===== Reset & Base ===== */
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
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .mono-data {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 16px 20px;
        }

        /* ===== Header ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: rgba(232, 165, 48, 0.12);
            border-radius: 8px;
            color: #e8a530;
        }

        .header h1 {
            font-size: 16px;
            font-weight: 600;
            color: #fafafa;
            letter-spacing: -0.01em;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e8a530 0%, #d4942a 100%);
            color: #0a0a0a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        .username {
            font-size: 13px;
            color: #a1a1aa;
            font-weight: 500;
        }

        .header-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: #71717a;
            text-decoration: none;
            transition: all 0.15s;
        }

        .header-icon:hover {
            background: #27272a;
            color: #e4e4e7;
        }

        /* ===== Breadcrumb ===== */
        .breadcrumb-bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 2px;
            padding: 10px 16px;
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .breadcrumb-bar a {
            color: #a1a1aa;
            text-decoration: none;
            padding: 4px 6px;
            border-radius: 5px;
            transition: all 0.15s;
        }

        .breadcrumb-bar a:hover {
            color: #fafafa;
            background: #27272a;
            text-decoration: none;
        }

        .breadcrumb-bar a.current {
            color: #e8e530;
            font-weight: 500;
            pointer-events: none;
        }

        .breadcrumb-bar .separator {
            color: #3f3f46;
            margin: 0 2px;
            font-size: 11px;
        }

        .up-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #27272a;
            border: 1px solid #3f3f46;
            border-radius: 6px;
            color: #a1a1aa;
            text-decoration: none;
            margin-right: 6px;
            transition: all 0.15s;
            flex-shrink: 0;
        }

        .up-btn:hover {
            background: #3f3f46;
            border-color: #52525b;
            color: #fafafa;
            text-decoration: none;
        }

        /* ===== Main Grid ===== */
        .main {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 12px;
        }

        /* ===== Sidebar ===== */
        .sidebar {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 12px;
            padding: 18px;
            height: fit-content;
            position: sticky;
            top: 16px;
        }

        .sidebar h2 {
            font-size: 11px;
            font-weight: 600;
            color: #71717a;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .upload-zone {
            border: 1.5px dashed #3f3f46;
            border-radius: 10px;
            padding: 22px 16px;
            text-align: center;
            margin-bottom: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: #e8a530;
            background: rgba(232, 165, 48, 0.04);
        }

        .upload-zone:focus {
            outline: 2px solid #e8a530;
            outline-offset: 2px;
        }

        .upload-icon {
            color: #52525b;
            margin-bottom: 10px;
            transition: color 0.2s;
        }

        .upload-zone:hover .upload-icon {
            color: #e8a530;
        }

        .upload-zone .text {
            font-size: 12px;
            color: #a1a1aa;
            line-height: 1.5;
        }

        .upload-zone .hint {
            font-size: 11px;
            color: #52525b;
            margin-top: 6px;
        }

        #fileInput {
            display: none;
        }

        .sidebar-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 14px;
        }

        /* ===== Buttons ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 14px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            border: none;
            width: 100%;
        }

        .btn-primary {
            background: #e8a530;
            color: #0a0a0a;
        }

        .btn-primary:hover {
            background: #d4942a;
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: #27272a;
            border: 1px solid #3f3f46;
            color: #d4d4d8;
        }

        .btn-secondary:hover {
            background: #3f3f46;
            border-color: #52525b;
        }

        /* ===== Stats ===== */
        .stats {
            padding-top: 14px;
            border-top: 1px solid #27272a;
        }

        .stat-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            font-size: 12px;
            color: #a1a1aa;
        }

        .stat-icon {
            font-size: 12px;
            width: 18px;
            text-align: center;
        }

        .stat-label {
            flex: 1;
        }

        .stat-value {
            color: #e4e4e7;
            font-weight: 500;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
        }

        /* ===== Progress ===== */
        .progress {
            display: none;
            margin: 14px 0;
        }

        .progress.active {
            display: block;
        }

        .progress-bar {
            height: 4px;
            background: #27272a;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #e8a530, #f0b840);
            transition: width 0.2s;
            width: 0%;
            border-radius: 2px;
        }

        .progress-text {
            font-size: 11px;
            color: #71717a;
            margin-top: 8px;
        }

        /* ===== Content Area ===== */
        .content {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 12px;
            overflow: hidden;
            min-width: 0;
        }

        /* ===== Toolbar ===== */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 16px;
            border-bottom: 1px solid #27272a;
        }

        .toolbar-left {
            flex: 1;
            min-width: 0;
        }

        .toolbar-right {
            flex-shrink: 0;
        }

        .search-box {
            position: relative;
            max-width: 320px;
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #52525b;
            pointer-events: none;
        }

        .search-box input {
            width: 100%;
            padding: 8px 10px 8px 32px;
            background: #0c0c0e;
            border: 1px solid #3f3f46;
            border-radius: 8px;
            color: #e4e4e7;
            font-family: inherit;
            font-size: 13px;
            transition: all 0.2s;
        }

        .search-box input::placeholder {
            color: #52525b;
        }

        .search-box input:focus {
            outline: none;
            border-color: #e8a530;
            box-shadow: 0 0 0 2px rgba(232, 165, 48, 0.12);
        }

        /* ===== Bulk Actions ===== */
        .bulk-actions {
            display: none;
            align-items: center;
            gap: 8px;
        }

        .bulk-actions.visible {
            display: flex;
        }

        .selected-count {
            font-size: 12px;
            color: #a1a1aa;
            white-space: nowrap;
            font-weight: 500;
        }

        .btn-bulk {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #3f3f46;
            background: transparent;
            color: #d4d4d8;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .btn-bulk-download {
            border-color: rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }

        .btn-bulk-download:hover {
            background: rgba(34, 197, 94, 0.08);
            border-color: #4ade80;
        }

        .btn-bulk-delete {
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .btn-bulk-delete:hover {
            background: rgba(239, 68, 68, 0.08);
            border-color: #f87171;
        }

        /* ===== File Table Header ===== */
        .content-header {
            display: grid;
            grid-template-columns: 36px 1fr 90px 140px 100px;
            padding: 0 16px;
            height: 40px;
            border-bottom: 1px solid #27272a;
            font-size: 11px;
            font-weight: 600;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            align-items: center;
            background: rgba(255, 255, 255, 0.015);
        }

        .content-header .sortable {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            user-select: none;
            transition: color 0.15s;
        }

        .content-header .sortable:hover {
            color: #d4d4d8;
        }

        .content-header .sortable.active-sort {
            color: #e8a530;
        }

        .sort-indicator {
            font-size: 9px;
            line-height: 1;
        }

        /* ===== File List ===== */
        .file-list {
            min-height: 360px;
        }

        .file-item {
            display: grid;
            grid-template-columns: 36px 1fr 90px 140px 100px;
            padding: 0 16px;
            height: 44px;
            border-bottom: 1px solid #1f1f23;
            font-size: 13px;
            transition: background 0.1s;
            align-items: center;
            position: relative;
        }

        .file-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: transparent;
            transition: background 0.15s;
        }

        .file-item:hover {
            background: rgba(255, 255, 255, 0.025);
        }

        .file-item:hover::before {
            background: #e8a530;
        }

        .file-item.selected {
            background: rgba(232, 165, 48, 0.06);
        }

        .file-item.selected::before {
            background: #e8a530;
        }

        .file-item[data-type="dir"] {
            cursor: pointer;
        }

        .file-item .name {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow: hidden;
            min-width: 0;
        }

        .file-item .name a,
        .file-item .name span.name-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-item .name a {
            color: #fafafa;
            text-decoration: none;
            font-weight: 500;
        }

        .file-item .name a:hover {
            color: #e8a530;
        }

        .file-item .name span.name-text {
            color: #e4e4e7;
        }

        .file-icon {
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }

        .dir-icon {
            color: #e8a530;
        }

        .file-item .size,
        .file-item .modified {
            color: #71717a;
        }

        .file-item .actions {
            display: flex;
            gap: 2px;
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
            color: #71717a;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-item .actions button:hover {
            color: #e8a530;
            background: rgba(232, 165, 48, 0.1);
        }

        .file-item .actions button.delete:hover {
            color: #f87171;
            background: rgba(239, 68, 68, 0.1);
        }

        /* ===== Checkbox Cells ===== */
        .checkbox-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .checkbox-cell input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border: 1.5px solid #3f3f46;
            border-radius: 4px;
            background: #0c0c0e;
            cursor: pointer;
            position: relative;
            transition: all 0.15s;
            flex-shrink: 0;
        }

        .checkbox-cell input[type="checkbox"]:hover {
            border-color: #71717a;
        }

        .checkbox-cell input[type="checkbox"]:checked {
            background: #e8a530;
            border-color: #e8a530;
        }

        .checkbox-cell input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 5px;
            width: 4px;
            height: 7px;
            border: solid #0a0a0a;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .header-cell input[type="checkbox"] {
            width: 15px;
            height: 15px;
        }

        /* ===== Empty State ===== */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
        }

        .empty-icon {
            color: #27272a;
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 15px;
            font-weight: 600;
            color: #a1a1aa;
            margin-bottom: 6px;
        }

        .empty-subtitle {
            font-size: 13px;
            color: #52525b;
        }

        /* ===== Modal ===== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 14px;
            padding: 28px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .modal-content h2 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 18px;
            color: #fafafa;
        }

        .modal-content input {
            width: 100%;
            padding: 10px 14px;
            background: #0c0c0e;
            border: 1px solid #3f3f46;
            border-radius: 8px;
            color: #e4e4e7;
            font-family: inherit;
            font-size: 14px;
            margin-bottom: 20px;
            transition: border-color 0.2s;
        }

        .modal-content input:focus {
            outline: none;
            border-color: #e8a530;
            box-shadow: 0 0 0 2px rgba(232, 165, 48, 0.12);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-actions .btn {
            width: auto;
            padding: 9px 20px;
        }

        /* ===== Toast ===== */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 13px;
            z-index: 2000;
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .toast.active {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.success {
            border-color: rgba(34, 197, 94, 0.4);
            color: #4ade80;
        }

        .toast.error {
            border-color: rgba(239, 68, 68, 0.4);
            color: #f87171;
        }

        /* ===== Loading Spinner ===== */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .spinner-overlay .spinner-text {
            background: #18181b;
            border: 1px solid #e8a530;
            border-radius: 10px;
            padding: 18px 28px;
            font-size: 14px;
            color: #e8a530;
            font-weight: 500;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        /* ===== Visually Hidden ===== */
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

        /* ===== Responsive / Mobile ===== */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .main {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                order: 2;
            }

            .content {
                order: 1;
            }

            .content-header {
                grid-template-columns: 32px 1fr 80px 60px;
            }

            .content-header .col-modified {
                display: none;
            }

            .file-item {
                grid-template-columns: 32px 1fr 80px 60px;
                height: 48px;
            }

            .file-item .modified {
                display: none;
            }

            .file-item .actions {
                opacity: 1;
            }

            .toolbar {
                flex-direction: column;
                gap: 8px;
            }

            .toolbar-left, .toolbar-right {
                width: 100%;
            }

            .search-box {
                max-width: none;
            }

            .bulk-actions {
                justify-content: flex-end;
            }

            .header h1 {
                font-size: 14px;
            }

            .username {
                display: none;
            }

            .content {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            body {
                overflow-x: hidden;
            }
        }

        @media (max-width: 480px) {
            .content-header {
                grid-template-columns: 32px 1fr 60px;
            }

            .content-header .col-size {
                display: none;
            }

            .file-item {
                grid-template-columns: 32px 1fr 60px;
            }

            .file-item .size {
                display: none;
            }

            .upload-zone {
                padding: 16px 12px;
            }

            .sidebar-buttons {
                flex-direction: row;
            }

            .sidebar-buttons .btn {
                font-size: 11px;
                padding: 8px 10px;
            }
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
    // Runtime error logging
    window.__jsErrors = [];
    window.onerror = function(msg, url, line, col, err) {
        window.__jsErrors.push({msg: String(msg), line: line, col: col, stack: err && err.stack});
        console.error('[UI Error]', msg, 'at line', line);
    };
    window.addEventListener('unhandledrejection', function(e) {
        window.__jsErrors.push({msg: 'Unhandled promise: ' + String(e.reason), type: 'promise'});
        console.error('[UI Promise Error]', e.reason);
    });
    (() => {
        // Current path and CSRF token
        const currentPath = <?php echo json_encode($path); ?>;
        const maxUploadSize = <?php echo (int)$max_upload_size; ?>;
        const csrfToken = <?php echo json_encode($csrf_token); ?>;
        const uploadMaxMb = <?php echo (int)$max_upload_mb; ?>;

        // ===== DOM refs =====
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const selectAllCheckbox = document.getElementById('selectAll');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCountEl = document.getElementById('selectedCount');

        // ===== Event Delegation for action buttons =====
        // Safety net: catches clicks on action buttons via data attributes
        fileList.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            const row = btn.closest('.file-item');
            if (!row) return;
            const path = row.getAttribute('data-path');
            const name = row.getAttribute('data-name');
            const origName = row.getAttribute('data-origname') || name;
            const action = btn.getAttribute('data-action');
            try {
                if (action === 'download') downloadFile(path);
                else if (action === 'rename') showRenameModal(origName, path);
                else if (action === 'delete') deleteItem(path, origName);
                else if (action === 'unzip') unzipFile(path, origName);
            } catch (err) { showToast('Action failed: ' + err.message, 'error'); }
        });

        // ===== Upload Zone =====
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
                        addFileToList(file.name, file.size);
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

        // ===== Add uploaded file to DOM =====
        function addFileToList(name, size) {
            // Remove empty state if present
            const emptyState = document.getElementById('emptyState');
            if (emptyState) emptyState.remove();

            const entryUrl = currentPath === '/' ? '/' + name : currentPath + '/' + name;
            const ext = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
            const isZip = ['zip','gz','tar','bz2','7z'].includes(ext);

            const row = document.createElement('div');
            row.className = 'file-item';
            row.setAttribute('data-path', entryUrl);
            row.setAttribute('data-type', 'file');
            row.setAttribute('data-name', name.toLowerCase());
            row.setAttribute('data-size', size);
            row.setAttribute('data-mtime', Math.floor(Date.now() / 1000));
            row.setAttribute('data-search', name.toLowerCase());
            row.setAttribute('data-zip', isZip);

            // Build action buttons
            let actionsHtml = '<button type="button" title="Download" onclick="downloadFile(\'' + entryUrl.replace(/'/g, "\\'") + '\')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></button>';
            actionsHtml += '<button type="button" title="Rename" onclick="showRenameModal(\'' + name.replace(/\\/g, '\\\\').replace(/'/g, "\\'") + '\', \'' + entryUrl.replace(/'/g, "\\'") + '\')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg></button>';
            actionsHtml += '<button type="button" class="delete" title="Delete" onclick="deleteItem(\'' + entryUrl.replace(/'/g, "\\'") + '\', \'' + name.replace(/\\/g, '\\\\').replace(/'/g, "\\'") + '\')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>';

            row.innerHTML =
                '<label class="checkbox-cell row-cell"><input type="checkbox" class="file-checkbox" value="' + entryUrl.replace(/"/g, '&quot;') + '"></label>' +
                '<div class="name col-name"><span class="file-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg></span><span class="name-text">' + name + '</span></div>' +
                '<div class="size col-size mono-data">' + formatBytes(size) + '</div>' +
                '<div class="modified col-modified mono-data">Just now</div>' +
                '<div class="actions col-actions">' + actionsHtml + '</div>';

            // Insert at top
            fileList.insertBefore(row, fileList.firstChild);

            // Re-sort if needed
            if (currentSort !== 'name' || !sortAsc) {
                sortByColumn(currentSort);
            }
        }

        // ===== Download =====
        function downloadFile(path) {
            window.location.href = '?action=download&path=' + path;
        }

        // ===== Delete =====
        async function deleteItem(path, name) {
            if (!confirm('Delete "' + name + '"?')) return;

            try {
                const response = await fetch('?action=delete&path=' + encodeURIComponent(path), {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: 'csrf_token=' + encodeURIComponent(csrfToken)
                });

                if (response.ok) {
                    showToast('Deleted: ' + name, 'success');
                    const item = fileList.querySelector('[data-path="' + CSS.escape(path) + '"]');
                    if (item) item.remove();
                    updateSelectionUI();
                    checkEmptyState();
                } else {
                    showToast('Delete failed: ' + await response.text(), 'error');
                }
            } catch (error) {
                showToast('Delete error: ' + error.message, 'error');
            }
        }

        // ===== Unzip / Extract =====
        async function unzipFile(path, name) {
            if (!confirm('Extract "' + name + '" into the current folder?')) return;
            showSpinner('Extracting…');
            try {
                const response = await fetch('?action=unzip&path=' + encodeURIComponent(path), {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: 'csrf_token=' + encodeURIComponent(csrfToken)
                });

                hideSpinner();
                if (response.ok) {
                    showToast('Extracted: ' + name, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast('Extract failed: ' + await response.text(), 'error');
                }
            } catch (error) {
                hideSpinner();
                showToast('Extract error: ' + error.message, 'error');
            }
        }

        // ===== Multi-Select =====
        function getCheckboxes() {
            return Array.from(fileList.querySelectorAll('.file-checkbox'));
        }

        function updateSelectionUI() {
            const checked = getCheckboxes().filter(cb => cb.checked);
            const count = checked.length;
            selectedCountEl.textContent = count + ' selected';

            if (count > 0) {
                bulkActions.classList.add('visible');
            } else {
                bulkActions.classList.remove('visible');
            }

            const total = getCheckboxes().length;
            selectAllCheckbox.checked = total > 0 && count === total;
            selectAllCheckbox.indeterminate = count > 0 && count < total;

            getCheckboxes().forEach(cb => {
                const row = cb.closest('.file-item');
                if (row) row.classList.toggle('selected', cb.checked);
            });
        }

        selectAllCheckbox.addEventListener('change', () => {
            const checked = selectAllCheckbox.checked;
            getCheckboxes().forEach(cb => {
                const row = cb.closest('.file-item');
                if (row && row.style.display !== 'none') cb.checked = checked;
            });
            updateSelectionUI();
        });

        fileList.addEventListener('change', (e) => {
            if (e.target.classList.contains('file-checkbox')) updateSelectionUI();
        });

        function getSelectedPaths() {
            return getCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
        }

        // ===== Bulk Download as ZIP =====
        async function bulkDownload() {
            const paths = getSelectedPaths();
            if (paths.length === 0) return;

            showSpinner('Creating ZIP…');
            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                paths.forEach(p => formData.append('paths[]', p));

                const response = await fetch('?action=zip-download&path=' + encodeURIComponent(currentPath), {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: formData
                });

                hideSpinner();
                if (response.ok) {
                    const blob = await response.blob();
                    const disposition = response.headers.get('Content-Disposition') || '';
                    let filename = 'download.zip';
                    const match = disposition.match(/filename\*?=(?:UTF-8''|"?)([^";\s]+)/i);
                    if (match) filename = decodeURIComponent(match[1]);

                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    showToast('ZIP downloaded (' + paths.length + ' items)', 'success');
                } else {
                    showToast('ZIP failed: ' + await response.text(), 'error');
                }
            } catch (error) {
                hideSpinner();
                showToast('ZIP error: ' + error.message, 'error');
            }
        }

        // ===== Bulk Delete =====
        async function bulkDelete() {
            const paths = getSelectedPaths();
            if (paths.length === 0) return;

            if (!confirm('Delete ' + paths.length + ' item(s)? This cannot be undone.')) return;

            showSpinner('Deleting…');
            let deleted = 0, failed = 0;

            for (const path of paths) {
                try {
                    const response = await fetch('?action=delete&path=' + encodeURIComponent(path), {
                        method: 'POST',
                        headers: { 'X-CSRF-Token': csrfToken },
                        body: 'csrf_token=' + encodeURIComponent(csrfToken)
                    });

                    if (response.ok) {
                        deleted++;
                        const item = fileList.querySelector('[data-path="' + CSS.escape(path) + '"]');
                        if (item) item.remove();
                    } else {
                        failed++;
                    }
                } catch (e) { failed++; }
            }

            hideSpinner();
            updateSelectionUI();
            if (deleted > 0) {
                showToast('Deleted ' + deleted + (failed ? ', ' + failed + ' failed' : ''), failed === 0 ? 'success' : 'error');
            }
            checkEmptyState();
        }

        function checkEmptyState() {
            if (fileList.querySelectorAll('.file-item').length === 0) {
                fileList.innerHTML = '<div class="empty-state" id="emptyState"><div class="empty-icon"><svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg></div><div class="empty-title">No files here</div><div class="empty-subtitle">Drop files or click Upload to get started</div></div>';
            }
        }

        // ===== Spinner =====
        function showSpinner(text) {
            const existing = document.querySelector('.spinner-overlay');
            if (existing) existing.remove();
            const overlay = document.createElement('div');
            overlay.className = 'spinner-overlay';
            overlay.innerHTML = '<div class="spinner-text">' + (text || 'Working…') + '</div>';
            document.body.appendChild(overlay);
        }

        function hideSpinner() {
            const overlay = document.querySelector('.spinner-overlay');
            if (overlay) overlay.remove();
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
            if (!name) return;

            const path = currentPath === '/' ? '/' + name : currentPath + '/' + name;

            try {
                const response = await fetch('?action=mkdir&path=' + encodeURIComponent(path), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'csrf_token=' + encodeURIComponent(csrfToken)
                });

                if (response.ok) {
                    showToast('Created: ' + name, 'success');
                    closeModal('newFolderModal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast('Create failed: ' + await response.text(), 'error');
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
            if (!newName) return;

            try {
                const response = await fetch('?action=rename&path=' + encodeURIComponent(renamePath), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'new_name=' + encodeURIComponent(newName) + '&csrf_token=' + encodeURIComponent(csrfToken)
                });

                if (response.ok) {
                    showToast('Renamed successfully', 'success');
                    closeModal('renameModal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast('Rename failed: ' + await response.text(), 'error');
                }
            } catch (error) {
                showToast('Rename error: ' + error.message, 'error');
            }
        }

        // ===== Modal Helpers =====
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            if (lastFocusedElement) {
                lastFocusedElement.focus();
                lastFocusedElement = null;
            }
        }

        function trapFocus(modal) {
            const focusable = modal.querySelectorAll('button, input, [tabindex]:not([tabindex="-1"])');
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            modal.addEventListener('keydown', (e) => {
                if (e.key !== 'Tab') return;
                if (e.shiftKey) {
                    if (document.activeElement === first) { e.preventDefault(); last.focus(); }
                } else {
                    if (document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                if (document.getElementById('newFolderModal').classList.contains('active')) { e.preventDefault(); createFolder(); }
                else if (document.getElementById('renameModal').classList.contains('active')) { e.preventDefault(); renameItem(); }
            }
        });

        // ===== Toast =====
        let toastTimer = null;
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast active ' + type;
            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('active'), 3000);
        }

        // ===== Close modals =====
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(m => closeModal(m.id));
            }
        });

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(modal.id); });
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

            let emptyState = document.getElementById('emptyState');
            if (visibleCount === 0 && q) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.id = 'emptyState';
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = '<div class="empty-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></div><div class="empty-title">No matching files</div><div class="empty-subtitle">Try a different search term</div>';
                    fileList.appendChild(emptyState);
                }
                emptyState.style.display = '';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }
        });

        // ===== Column-header Sorting =====
        let currentSort = 'name';
        let sortAsc = true;

        function sortByColumn(field) {
            if (currentSort === field) {
                sortAsc = !sortAsc;
            } else {
                currentSort = field;
                sortAsc = true;
            }

            // Update header indicators
            document.querySelectorAll('.content-header .sortable').forEach(el => {
                el.classList.remove('active-sort');
                const ind = el.querySelector('.sort-indicator');
                if (ind) ind.textContent = '';
            });

            const activeHeader = document.querySelector('.content-header [data-sort="' + field + '"]');
            if (activeHeader) {
                activeHeader.classList.add('active-sort');
                const indicator = activeHeader.querySelector('.sort-indicator');
                if (indicator) indicator.textContent = sortAsc ? '▲' : '▼';
            }

            const items = Array.from(fileList.querySelectorAll('.file-item'));

            items.sort((a, b) => {
                const aDir = a.getAttribute('data-type') === 'dir';
                const bDir = b.getAttribute('data-type') === 'dir';
                if (aDir && !bDir) return -1;
                if (!aDir && bDir) return 1;

                let va, vb;
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

            const emptyState = document.getElementById('emptyState');
            items.forEach(item => fileList.appendChild(item));
            if (emptyState) fileList.appendChild(emptyState);
        }

        // Initialize default sort indicator
        const defaultIndicator = document.getElementById('sort-indicator-name');
        if (defaultIndicator) defaultIndicator.textContent = '▲';
        const defaultSortHeader = document.querySelector('.content-header [data-sort="name"]');
        if (defaultSortHeader) defaultSortHeader.classList.add('active-sort');

        // ===== Keyboard shortcuts =====
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            if (e.key === 'a' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                getCheckboxes().forEach(cb => {
                    const row = cb.closest('.file-item');
                    if (row && row.style.display !== 'none') cb.checked = true;
                });
                updateSelectionUI();
            }

            if (e.key === 'Delete' && getSelectedPaths().length > 0) {
                bulkDelete();
            }
        });

        // ===== Global exports =====
        window.downloadFile = downloadFile;
        window.deleteItem = deleteItem;
        window.unzipFile = unzipFile;
        window.showNewFolderModal = showNewFolderModal;
        window.createFolder = createFolder;
        window.showRenameModal = showRenameModal;
        window.renameItem = renameItem;
        window.closeModal = closeModal;
        window.sortByColumn = sortByColumn;
        window.bulkDownload = bulkDownload;
        window.bulkDelete = bulkDelete;
    })();
    </script>
<?php
}