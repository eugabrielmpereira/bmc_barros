<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

use Core\App;

function renderBootstrapError(string $message, ?string $debug = null): never
{
    http_response_code(500);

    $code = 500;
    require SHARED_PATH . '/View/errors/500.php';
    exit;
}

function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $fatal = in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    if ($fatal) {
        $debug = is_production() ? null : "Error [{$errno}] {$errstr} in {$errfile}:{$errline}";
        renderBootstrapError('Erro interno do servidor.', $debug);

        return true;
    }

    error_log("PHP [{$errno}] {$errstr} in {$errfile}:{$errline}");

    return false;
}

function exceptionHandler($exception) {
    $debug = is_production()
        ? null
        : "Exception: " . $exception->getMessage() . "\n"
            . "File: " . $exception->getFile() . ":" . $exception->getLine() . "\n"
            . $exception->getTraceAsString();

    renderBootstrapError('Erro interno do servidor.', $debug);
}

function shutdownHandler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $debug = is_production()
            ? null
            : "Fatal Error: " . $error['message'] . "\n"
                . "File: " . $error['file'] . ":" . $error['line'];

        renderBootstrapError('Erro interno do servidor.', $debug);
    }
}

set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');
register_shutdown_function('shutdownHandler');

$app = new App();
$app->run();
