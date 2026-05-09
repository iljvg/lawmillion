<?php
declare(strict_types=1);

/**
 * Minimal regex-based router. Maps clean URL patterns to controller@method.
 * Patterns use {param} placeholders; values are passed in order to the handler.
 *
 *   $router->get('/practice-areas/{slug}/', 'PracticeAreaController@show');
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:string}> */
    private array $routes = [];

    public function get(string $pattern, string $handler): void
    {
        $this->routes[] = ['method' => 'GET', 'pattern' => $pattern, 'handler' => $handler];
    }

    public function post(string $pattern, string $handler): void
    {
        $this->routes[] = ['method' => 'POST', 'pattern' => $pattern, 'handler' => $handler];
    }

    public function any(string $pattern, string $handler): void
    {
        $this->routes[] = ['method' => '*', 'pattern' => $pattern, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Strip query string, normalize trailing slash for non-root paths
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = $this->canonicalize($path, $method);

        // Look up DB-driven 301 redirects first (allows ops to add redirects without redeploying)
        if ($newPath = $this->lookupRedirect($path)) {
            header('Location: ' . SITE_URL . $newPath, true, 301);
            exit;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $method) {
                continue;
            }
            $regex = $this->patternToRegex($route['pattern']);
            if (preg_match($regex, $path, $matches)) {
                // Drop full match, keep only positional captures (named groups
                // appear as both numeric AND string keys; call_user_func_array
                // would interpret string keys as named args in PHP 8 → fatal).
                array_shift($matches);
                $params = array_values(array_filter(
                    $matches,
                    fn($k) => is_int($k),
                    ARRAY_FILTER_USE_KEY
                ));
                $this->invoke($route['handler'], $params);
                return;
            }
        }

        // Nothing matched -> 404
        http_response_code(404);
        $this->invoke('ErrorController@notFound', []);
    }

    /**
     * Force lowercase + trailing slash for everything except files (.xml, .txt).
     * Issues a 301 if the URL doesn't match canonical form.
     * Skipped for non-GET methods so POST to /leads/submit is not redirected.
     */
    private function canonicalize(string $path, string $method = 'GET'): string
    {
        if ($method !== 'GET' && $method !== 'HEAD') {
            return $path;
        }

        $original = $path;

        // Lowercase
        $path = strtolower($path);

        // Add trailing slash to directory-style paths
        if (!preg_match('/\.[a-z0-9]+$/i', $path) && !str_ends_with($path, '/')) {
            $path .= '/';
        }

        if ($path !== $original) {
            header('Location: ' . SITE_URL . $path, true, 301);
            exit;
        }
        return $path;
    }

    private function lookupRedirect(string $path): ?string
    {
        try {
            // Try exact match first; if no hit, try without trailing slash
            // (covers cases where the seed stored '/foo' but canonicalization
            // already added '/' on the way in).
            $candidates = [$path];
            if (str_ends_with($path, '/') && $path !== '/') {
                $candidates[] = rtrim($path, '/');
            }
            $row = null;
            foreach ($candidates as $cand) {
                $row = Database::one(
                    'SELECT old_path, new_path FROM redirects WHERE old_path = ? AND is_active = 1 LIMIT 1',
                    [$cand]
                );
                if ($row) break;
            }
            if ($row) {
                Database::exec(
                    'UPDATE redirects SET hit_count = hit_count + 1, last_hit_at = NOW() WHERE old_path = ?',
                    [$row['old_path']]
                );
                return $row['new_path'];
            }
        } catch (Throwable $e) {
            // Database might not be reachable on first run — fall through to routes
        }
        return null;
    }

    private function patternToRegex(string $pattern): string
    {
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }

    private function invoke(string $handler, array $params): void
    {
        [$class, $method] = explode('@', $handler);
        $controllerFile = APP_PATH . '/controllers/' . $class . '.php';
        if (!file_exists($controllerFile)) {
            throw new RuntimeException("Controller not found: {$class}");
        }
        require_once $controllerFile;
        $instance = new $class();
        call_user_func_array([$instance, $method], $params);
    }
}
