<?php
/**
 * Directory scanning utilities.
 *
 * Provides sorted directory listing with dotfile filtering.
 */

/**
 * Scan directory and return sorted entries.
 *
 * @param string $path Directory path
 * @param bool $hide_dotfiles Whether to hide dotfiles
 * @return array Sorted list of filenames
 */
function scan_directory(string $path, bool $hide_dotfiles): array {
    $entries = scandir($path);
    if ($entries === false) {
        return [];
    }

    // Filter dotfiles if configured
    if ($hide_dotfiles) {
        $entries = array_filter($entries, function ($entry) {
            return $entry[0] !== '.';
        });
    }

    // Filter .part.* temp files (incomplete uploads)
    $entries = array_filter($entries, function ($entry) {
        return !preg_match('/\\.part\\.[a-f0-9.]+$/', $entry);
    });

    // Sort: directories first, then files, alphabetically
    usort($entries, function ($a, $b) use ($path) {
        $a_is_dir = is_dir($path . '/' . $a);
        $b_is_dir = is_dir($path . '/' . $b);

        if ($a_is_dir && !$b_is_dir) {
            return -1;
        }
        if (!$a_is_dir && $b_is_dir) {
            return 1;
        }

        return strcasecmp($a, $b);
    });

    return $entries;
}
