<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Regex router. Route params: {id} (digits), {token} (url-safe), {slug}.
 * Middleware strings: 'auth', 'guest', 'can:perm.name'.
 * All POST routes are CSRF-verified centrally.
 */
final class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $path, array|\Closure $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|\Closure $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(array $options, callable $routes): void
    {
        $prevMw = $this->groupMiddleware;
        $prevPrefix = $this->groupPrefix;
        $this->groupMiddleware = [...$this->groupMiddleware, ...($options['middleware'] ?? [])];
        $this->groupPrefix .= $options['prefix'] ?? '';
        $routes($this);
        $this->groupMiddleware = $prevMw;
        $this->groupPrefix = $prevPrefix;
    }

    private function add(string $method, string $path, array|\Closure $handler, array $middleware): void
    {
        $full = rtrim($this->groupPrefix . $path, '/') ?: '/';
        $regex = preg_replace(
            ['#\{id\}#', '#\{token\}#', '#\{slug\}#'],
            ['(\d{1,10})', '([A-Za-z0-9_\-]{8,128})', '([a-z0-9_\-]{1,64})'],
            $full
        );
        $this->routes[$method][] = [
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => [...$this->groupMiddleware, ...$middleware],
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $allowed = false;
        foreach ($this->routes as $m => $routes) {
            foreach ($routes as $route) {
                if (!preg_match($route['regex'], $path, $matches)) {
                    continue;
                }
                if ($m !== $method) {
                    $allowed = true;
                    continue;
                }
                array_shift($matches);
                $params = array_map('intval_if_numeric', $matches);

                if ($method === 'POST') {
                    Csrf::verify();
                }
                foreach ($route['middleware'] as $mw) {
                    \App\Middleware\Middleware::handle($mw);
                }

                $handler = $route['handler'];
                try {
                    if ($handler instanceof \Closure) {
                        $handler(...$params);
                    } else {
                        [$class, $action] = $handler;
                        (new $class())->$action(...$params);
                    }
                } catch (ValidationException $e) {
                    self::validationFailed($e);
                }
                return;
            }
        }
        throw new HttpException($allowed ? 405 : 404);
    }

    /** User-correctable error: JSON 422 for AJAX, otherwise redirect back with errors + old input. */
    private static function validationFailed(ValidationException $e): never
    {
        $general = $e->errors['_'] ?? null;
        if (Request::isAjax()) {
            Response::json(['ok' => false, 'message' => $general ?? $e->getMessage(), 'errors' => $e->errors], 422);
        }
        $old = $_POST;
        foreach (['password', 'password_confirmation', 'current_password', '_token'] as $k) {
            unset($old[$k]);
        }
        Session::flash('_errors', $e->errors);
        Session::flash('_old', $old);
        Session::flash('_alert', ['type' => 'danger', 'message' => $general ?? $e->getMessage()]);
        Response::back();
    }
}
