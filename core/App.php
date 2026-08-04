<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\HttpException;
use Core\Exceptions\NotFoundException;
use Core\Middleware\MiddlewareInterface;
use Core\Middleware\MiddlewarePipeline;
use Throwable;

class App
{
    private Router $router;

    /** @var array<string, list<class-string<MiddlewareInterface>>> */
    private array $middlewareGroups;

    public function __construct()
    {
        $this->router = new Router();
        $this->middlewareGroups = require __DIR__ . '/../config/middleware.php';
        $this->loadRoutes();
    }

    public function run(): void
    {
        try {
            $request = Request::capture();
            $route = $this->router->resolve($request->method(), $request->uri());

            if ($route === null) {
                throw new NotFoundException();
            }

            $middlewares = $this->resolveMiddlewareStack($route['middleware']);
            $pipeline = new MiddlewarePipeline($middlewares);

            $response = $pipeline->handle(
                $request,
                fn (Request $req): Response => $this->invokeHandler($route['handler'], $route['params'])
            );

            $response->send();
        } catch (HttpException $e) {
            $this->renderException($e)->send();
        } catch (Throwable $e) {
            error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->renderException($this->wrapThrowable($e))->send();
        }
    }

    private function loadRoutes(): void
    {
        $routes = require __DIR__ . '/../config/routes.php';
        foreach ($routes as $route) {
            $this->router->add(
                $route['method'],
                $route['path'],
                $route['handler'],
                $route['middleware'] ?? 'web'
            );
        }
    }

    /**
     * @return MiddlewareInterface[]
     */
    private function resolveMiddlewareStack(string $group): array
    {
        $classNames = $this->middlewareGroups[$group] ?? [];
        $instances = [];

        foreach ($classNames as $className) {
            if (!is_string($className) || $className === '') {
                continue;
            }
            if (!class_exists($className)) {
                error_log("[App] Middleware não encontrado: {$className}");
                continue;
            }
            $instance = new $className();
            if (!$instance instanceof MiddlewareInterface) {
                error_log("[App] Classe não implementa MiddlewareInterface: {$className}");
                continue;
            }
            $instances[] = $instance;
        }

        return $instances;
    }

    /**
     * @param callable|array{class-string, string} $handler
     * @param array<string, string> $params
     */
    private function invokeHandler(callable|array $handler, array $params): Response
    {
        ob_start();

        try {
            if (is_array($handler)) {
                [$class, $method] = $handler;
                $instance = new $class();
                call_user_func_array([$instance, $method], $params);
            } else {
                call_user_func_array($handler, $params);
            }
        } finally {
            $output = ob_get_clean() ?: '';
        }

        if ($output !== '') {
            return Response::html($output);
        }

        return Response::empty();
    }

    private function renderException(HttpException $e): Response
    {
        $code = $e->statusCode();
        $message = $e->getMessage() !== '' ? $e->getMessage() : $this->defaultMessage($code);
        $view = $this->errorViewPath($code);

        if (!is_file($view)) {
            return Response::html(
                '<h1>' . $code . '</h1><p>' . htmlspecialchars($message) . '</p>',
                $code
            );
        }

        ob_start();
        extract([
            'code' => $code,
            'message' => $message,
            'debug' => $e->context()['debug'] ?? null,
        ], EXTR_SKIP);
        require $view;
        $body = ob_get_clean() ?: '';

        return Response::html($body, $code);
    }

    private function wrapThrowable(Throwable $e): HttpException
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            return new HttpException(
                500,
                'Erro interno do servidor.',
                [
                    'debug' => $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine(),
                ],
                $e
            );
        }

        return new HttpException(500, 'Erro interno do servidor.', [], $e);
    }

    private function errorViewPath(int $code): string
    {
        $specific = SHARED_PATH . '/View/errors/' . $code . '.php';
        if (is_file($specific)) {
            return $specific;
        }

        return match ($code) {
            403 => SHARED_PATH . '/View/errors/403.php',
            404 => SHARED_PATH . '/View/errors/404.php',
            default => SHARED_PATH . '/View/errors/500.php',
        };
    }

    private function defaultMessage(int $code): string
    {
        return match ($code) {
            401 => 'Não autenticado.',
            403 => 'Acesso negado.',
            404 => 'Página não encontrada.',
            422 => 'Dados inválidos.',
            default => 'Erro interno do servidor.',
        };
    }
}
