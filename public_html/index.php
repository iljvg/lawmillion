<?php
declare(strict_types=1);

/**
 * LawMillion.com — Front Controller
 * All requests funnel through here via .htaccess rewrite.
 */

require_once dirname(__DIR__) . '/app/config/config.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/SEO.php';
require_once APP_PATH . '/core/Router.php';

// Force HTTPS in production
// Force HTTPS in production
// Hostinger/Cloudflare terminate SSL at their proxy, so $_SERVER['HTTPS']
// is empty in PHP even when the user's actual request is HTTPS. We must
// also check the X-Forwarded-Proto header, which the proxy sets to 'https'.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['HTTP_X_FORWARDED_SSL']   ?? '') === 'on')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

if (!APP_DEBUG && !$isHttps) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}
// Strip "www." -> non-www canonical
if (isset($_SERVER['HTTP_HOST']) && str_starts_with($_SERVER['HTTP_HOST'], 'www.')) {
    $host = substr($_SERVER['HTTP_HOST'], 4);
    header('Location: https://' . $host . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

// Security headers (defense in depth)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
if (!APP_DEBUG) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Start session early — many views call csrf_token() which needs a started
// session. Starting it here (before any output) prevents "headers already
// sent" warnings.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !APP_DEBUG,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$router = new Router();

// -------- Static files served by SitemapController --------
$router->get('/sitemap.xml', 'SitemapController@index');
$router->get('/robots.txt',  'SitemapController@robots');
$router->get('/llms.txt',    'SitemapController@llms');

// -------- Homepage --------
$router->get('/', 'HomeController@index');

// -------- Practice Areas --------
$router->get('/practice-areas/',         'PracticeAreaController@index');
$router->get('/practice-areas/{slug}/',  'PracticeAreaController@show');

// -------- Find a Lawyer + Lead submissions --------
$router->get ('/find-a-lawyer/', 'DirectoryController@index');
$router->post('/leads/submit',   'DirectoryController@submitLead');

// -------- Attorney profile pages --------
$router->get('/attorneys/{state}/{city}/{slug}/', 'AttorneyController@show');

// -------- Blog --------
$router->get('/blog/',         'BlogController@index');
$router->get('/blog/{slug}/',  'BlogController@show');

// -------- Attorney Portal --------
$router->get ('/for-attorneys/',                'AttorneyPortalController@landing');
$router->get ('/for-attorneys/login/',          'AttorneyPortalController@loginForm');
$router->post('/for-attorneys/login/',          'AttorneyPortalController@loginSubmit');
$router->post('/for-attorneys/logout/',         'AttorneyPortalController@logout');
$router->get ('/for-attorneys/create-profile/', 'AttorneyPortalController@signupForm');
$router->post('/for-attorneys/create-profile/', 'AttorneyPortalController@signupSubmit');
$router->get ('/for-attorneys/dashboard/',            'AttorneyPortalController@dashboard');
$router->post('/for-attorneys/dashboard/place-bid',   'AttorneyPortalController@placeBid');
$router->get ('/for-attorneys/dashboard/state',       'AttorneyPortalController@dashboardState');

// Dispatch
try {
    $router->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_SERVER['REQUEST_URI']    ?? '/'
    );
} catch (Throwable $e) {
    if (APP_DEBUG) {
        echo '<pre>' . e($e->getMessage()) . "\n\n" . e($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        require_once APP_PATH . '/controllers/SitemapController.php';
        // Render a clean 500 view if available; fallback to plaintext
        $viewFile = VIEW_PATH . '/errors/500.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo '<h1>Something went wrong</h1><p>Please try again shortly.</p>';
        }
        error_log('[LawMillion] ' . $e->getMessage());
    }
}
