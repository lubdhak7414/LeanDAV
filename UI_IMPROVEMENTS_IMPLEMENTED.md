# UI Improvements Implemented

This document summarizes all changes made from the `ui_improvement.md` audit.

## Files Modified

- `lib/ui/handler.php` - Security fixes
- `lib/ui/dashboard.php` - Accessibility & HTML improvements  
- `lib/ui/assets.php` - CSS & JavaScript improvements

---

## Security Fixes (Critical & High)

### 1. Upload Filename Sanitization (Critical)
**Before:** Raw user-supplied filename appended to path
**After:** `sanitize_name()` applied to upload filenames

```php
$safe_name = sanitize_name($file['name']);
if ($safe_name === false || $safe_name === '') {
    http_response_code(400);
    echo 'Invalid filename';
    return;
}
```

### 2. mkdir Path Traversal Fix (Critical)
**Before:** No validation for non-existent paths
**After:** Parent directory validated via `realpath()` before allowing mkdir

```php
if ($resolved === false && $action === 'mkdir') {
    $storage_real = realpath($storage);
    $check_path = $storage . '/' . ltrim($path, '/');
    $normalized = realpath(dirname($check_path));
    if ($storage_real === false || $normalized === false || !str_starts_with($normalized, $storage_real)) {
        http_response_code(403);
        echo 'Forbidden';
        return;
    }
}
```

### 3. File Streaming (High)
**Before:** `readfile()` loads entire file into memory
**After:** Chunked `fopen/fread` loop with 8KB chunks + `exit` after stream

```php
$handle = fopen($full_path, 'rb');
while (!feof($handle)) {
    echo fread($handle, 8192);
    flush();
}
fclose($handle);
exit;
```

### 4. XSS: Unsafe JS Escaping (High)
**Before:** `addcslashes()` for JS string injection
**After:** `json_encode()` with proper flags

```php
onclick="showRenameModal(<?php echo json_encode($entry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>"
```

### 5. XSS: htmlspecialchars Encoding (High)
**Before:** `htmlspecialchars()` without explicit flags
**After:** `rawurlencode()` for URL contexts

### 6. Rename Path Separator Check (Medium)
**Added:** Validation to prevent renaming with path separators

```php
if (str_contains($new_name, '/') || str_contains($new_name, '\\')) {
    http_response_code(400);
    echo 'Invalid name';
    return;
}
```

---

## Accessibility Fixes

### 1. Semantic HTML & ARIA
- Modals now have `role="dialog"`, `aria-modal="true"`, `aria-labelledby`
- Progress bar has `role="progressbar"`, `aria-valuemin/max/now`
- Toast has `role="status"`, `aria-live="polite"`, `aria-atomic="true"`
- Upload zone has `role="button"` and `tabindex="0"`

### 2. Form Labels
- Search input: `<label class="visually-hidden">Filter files by name</label>`
- Modal inputs: `<label class="visually-hidden">Folder name</label>`
- Added `.visually-hidden` CSS class

### 3. Icon-Only Buttons
- All action buttons now have `aria-label` (e.g., `aria-label="Delete filename"`)
- Emoji wrapped in `<span aria-hidden="true">`
- Added `type="button"` to all buttons

### 4. Color Contrast
- Changed `#5a5550` → `#a09b94` (secondary text)
- Changed `#3a3a3a` → `#777` (placeholders)
- Achieves ≥4.5:1 contrast ratio (WCAG 2.1 AA)

### 5. Keyboard Visibility
- Action buttons now visible on `:focus-within` (not just hover)
- Upload zone keyboard accessible

---

## UI/UX Fixes

### 1. Empty State During Filtering
- Shows "No matching files" when search filters hide all items

### 2. Sort Button Text Reset
- Buttons now reset to base labels before updating active one
- Uses `sortLabels` object for consistent text

### 3. Max Upload Size Hint
- Upload zone now displays "Max X MB per file"

### 4. Cursor Pointer
- Only directories show pointer cursor (not files)

### 5. Focus Management
- Modals save/restore focus to trigger element
- Focus trap implemented for modals
- ESC key closes modals

---

## Frontend Code Quality

### 1. IIFE Wrapper
- All JS wrapped in `(() => { ... })()`
- Only explicitly exported functions on `window`

### 2. use strict
- Added `"use strict"` directive

### 3. Upload Queue Fix
- `uploading = false` properly reset when queue completes

### 4. Delete Without Reload
- Deleted items removed from DOM instead of page reload

### 5. CSS Specificity
- Removed `!important` from media queries

---

## PHP Quality

### 1. Error Handling
- `@filesize()` and `@filemtime()` with false checks
- Prevents TypeError on permission errors

---

## Summary of Improvements

| Category | Count |
|----------|-------|
| Security Critical | 2 |
| Security High | 3 |
| Security Medium | 1 |
| Accessibility High | 5 |
| Accessibility Medium | 3 |
| UI/UX | 5 |
| Code Quality | 5 |
| PHP Quality | 1 |
| **Total** | **25** |
