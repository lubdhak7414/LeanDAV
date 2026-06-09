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
    // TODO: Implement in Phase 13
}

/**
 * Handle UNLOCK request.
 * 
 * @param array $config Configuration array
 * @param string $path Resource path
 * @return void
 */
function handle_unlock(array $config, string $path): void {
    // TODO: Implement in Phase 13
}

/**
 * Check if resource is locked.
 * 
 * @param string $path Resource path
 * @return bool True if locked
 */
function is_locked(string $path): bool {
    // TODO: Implement in Phase 13
    return false;
}

/**
 * Garbage collect expired locks.
 * 
 * @param string $lock_dir Lock directory
 * @return void
 */
function gc_locks(string $lock_dir): void {
    // TODO: Implement in Phase 13
}