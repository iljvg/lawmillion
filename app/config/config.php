<?php
declare(strict_types=1);

/**
 * LawMillion.us — App Configuration
 * --------------------------------------------------------------------------
 * US-only legal directory. All defaults reflect US locale, currency,
 * and timezone. Sensitive values (DB password, mail keys) MUST come from
 * environment variables in production.
 */

// -------- ENV --------
/**
 * Pull a value from $_SERVER, $_ENV, or getenv() — works across SAPIs
 * (Apache mod_php + SetEnv, php-fpm + clear_env=no, CLI server, Docker, etc.)
 */
function env_var(string $key, $default = null)
{
    return $_SERVER[$key] ?? $_ENV[$key] ?? (getenv($key) !== false ? getenv($key) : $default);
}
// define('APP_ENV',   env_var('APP_ENV', 'production'));      // 'production' | 'development'
define('APP_ENV',   'development');
define('APP_DEBUG', APP_ENV === 'development');


// -------- SITE --------
define('SITE_NAME',    'LawMillion');
define('SITE_DOMAIN',  'lawmillion.us');
define('SITE_URL',     'https://lawmillion.us');           // canonical, no trailing slash
define('SITE_EMAIL',   'support@lawmillion.us');
define('SITE_PHONE',   '+18005296454');                     // US toll-free

// -------- LOCALE (US-only) --------
define('SITE_LOCALE',   'en_US');
define('SITE_LANGUAGE', 'en-US');                           // hreflang
define('SITE_COUNTRY',  'US');
define('SITE_CURRENCY', 'USD');
define('SITE_TIMEZONE', 'America/New_York');                // canonical US timezone for dates
date_default_timezone_set(SITE_TIMEZONE);

// -------- DATABASE --------
define('DB_HOST',     env_var('DB_HOST',     'localhost'));
define('DB_PORT',     (int) env_var('DB_PORT', 3306));
define('DB_NAME',     env_var('DB_NAME',     'u647763412_lawmillion'));
define('DB_USER',     env_var('DB_USER',     'u647763412_lawmillion'));
define('DB_PASSWORD', env_var('DB_PASSWORD', '$Wn4JPTb6'));
define('DB_CHARSET',  'utf8mb4');

// -------- SECURITY --------
define('CSRF_TOKEN_NAME', 'lm_csrf');
define('SESSION_NAME',    'lm_session');
define('PASSWORD_ALGO',   PASSWORD_BCRYPT);
define('PASSWORD_COST',   12);

// -------- PATHS --------
define('BASE_PATH',    realpath(__DIR__ . '/../..'));
define('APP_PATH',     BASE_PATH . '/app');
define('VIEW_PATH',    APP_PATH  . '/views');
define('PUBLIC_PATH',  BASE_PATH . '/public');

// -------- ERROR HANDLING --------
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
