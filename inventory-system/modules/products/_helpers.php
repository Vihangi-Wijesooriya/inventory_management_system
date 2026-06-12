<?php
/**
 * Product helper functions — used by create.php and edit.php.
 */

/**
 * Validate and save an uploaded image. Returns the stored filename or null.
 * Throws on invalid input.
 */
function save_product_image(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed (code ' . $file['error'] . ').');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Image must be 2 MB or smaller.');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, GIF and WebP images are allowed.');
    }

    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0775, true);
    }

    $filename = 'prod_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $target   = UPLOAD_PATH . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Could not save uploaded image.');
    }
    return $filename;
}

/** Delete a stored product image, if any. */
function delete_product_image(?string $filename): void
{
    if (!$filename) return;
    $path = UPLOAD_PATH . '/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}
