<?php
declare(strict_types=1);

/**
 * Global helper functions (loaded once at bootstrap).
 */

/** HTML-safe output. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format a US phone number (+12145551717 -> (214) 555-1717). */
function us_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
        $digits = substr($digits, 1);
    }
    if (strlen($digits) !== 10) {
        return $phone;
    }
    return sprintf('(%s) %s-%s',
        substr($digits, 0, 3),
        substr($digits, 3, 3),
        substr($digits, 6, 4)
    );
}

/** Format USD currency. */
function usd(int|float $amount): string
{
    return '$' . number_format((float)$amount, 0);
}

/** Format a US-style date (MM/DD/YYYY). */
function us_date(string $datetime): string
{
    return (new DateTimeImmutable($datetime))->format('m/d/Y');
}

/** Long-form date for articles ("April 2, 2026"). */
function long_date(string $datetime): string
{
    return (new DateTimeImmutable($datetime))->format('F j, Y');
}

/** Generate / fetch a CSRF token for the current session. */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/** Verify a submitted CSRF token. */
function csrf_check(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return !empty($_SESSION[CSRF_TOKEN_NAME])
        && is_string($token)
        && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/** Build an absolute URL from a relative path. */
function url(string $path = ''): string
{
    return SITE_URL . '/' . ltrim($path, '/');
}

/** Human-readable relative time ("2m ago", "3hr ago", "5d ago"). */
function time_ago(?string $datetime): string
{
    if (!$datetime) return '';
    $diff = time() - strtotime($datetime);
    if ($diff < 5)     return 'just now';
    if ($diff < 60)    return $diff . 's ago';
    if ($diff < 3600)  return (int)floor($diff / 60)   . 'm ago';
    if ($diff < 86400) return (int)floor($diff / 3600) . 'hr ago';
    return (int)floor($diff / 86400) . 'd ago';
}

/** Two-letter initials from first/last names ("Amanda","Richardson" -> "AR"). */
function initials(string $first, string $last): string
{
    $f = mb_substr(trim($first), 0, 1);
    $l = mb_substr(trim($last),  0, 1);
    return strtoupper($f . $l);
}

/** Slugify a string (e.g., "Amanda J. Richardson" -> "amanda-j-richardson"). */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/** Render a view file with extracted data. */
function view(string $path, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $file = VIEW_PATH . '/' . ltrim($path, '/');
    if (!str_ends_with($file, '.php')) {
        $file .= '.php';
    }
    if (!file_exists($file)) {
        throw new RuntimeException("View not found: {$path}");
    }
    require $file;
}

/** Redirect helper. */
function redirect(string $path, int $code = 302): void
{
    header('Location: ' . SITE_URL . $path, true, $code);
    exit;
}

/**
 * mbstring polyfills — many shared hosts disable ext-mbstring.
 * If the real functions exist, our shims are skipped.
 */
if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = null) {
        return $length === null ? substr((string)$str, (int)$start) : substr((string)$str, (int)$start, (int)$length);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($str) {
        return strlen((string)$str);
    }
}
