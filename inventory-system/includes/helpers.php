<?php
/**
 * Generic helper functions used across the app.
 */

/** HTML-escape a string for safe output. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Build a URL relative to BASE_URL. */
function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/** Redirect helper. Stops execution. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Set a one-time flash message. type = success|danger|warning|info */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** Pop all flash messages (for rendering in layout). */
function take_flash(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

/** CSRF: generate or fetch token for this session. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** CSRF hidden input. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Verify submitted CSRF token; aborts on failure. */
function csrf_verify(): void
{
    $submitted = $_POST['_csrf'] ?? '';
    if (!is_string($submitted) || !hash_equals($_SESSION['_csrf'] ?? '', $submitted)) {
        http_response_code(419);
        die('Invalid CSRF token. Please reload the page and try again.');
    }
}

/** Format money with currency symbol. */
function money($amount): string
{
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2);
}

/** Format a date string for display. */
function fmt_date(?string $date): string
{
    if (!$date) return '-';
    $ts = strtotime($date);
    return $ts ? date('d M Y', $ts) : '-';
}

/** Format a datetime for display. */
function fmt_datetime(?string $datetime): string
{
    if (!$datetime) return '-';
    $ts = strtotime($datetime);
    return $ts ? date('d M Y, H:i', $ts) : '-';
}

/** Generate a unique reference number, e.g. PUR-20260521-XXXX or SAL-... */
function generate_reference(string $prefix): string
{
    return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/** Return old form value (for re-populating after validation errors). */
function old(string $key, $default = ''): string
{
    $val = $_SESSION['_old'][$key] ?? $default;
    return e($val);
}

/** Stash form input for re-display after redirect. */
function flash_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

/** Pop old input after rendering form. */
function take_old(): void
{
    unset($_SESSION['_old']);
}
