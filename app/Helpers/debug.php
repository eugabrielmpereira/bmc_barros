<?php

declare(strict_types=1);

if (!function_exists('dd_log')) {
    function dd_log(string $message, ?array $context = null): void {
        $log = [
            'timestamp' => now(),
            'message' => $message,
            'context' => $context ?? [],
        ];

        error_log(json_encode($log));
    }
}

if (!function_exists('log_error')) {
    function log_error(string $message, ?\Throwable $exception = null): void {
        $line = $exception ? $exception->getLine() : 0;
        $file = $exception ? $exception->getFile() : '';

        $msg = "[$file:$line] $message";
        error_log($msg);
    }
}

if (!function_exists('log_audit')) {
    function log_audit(string $action, string $table, int $userId, $beforeData = null, $afterData = null): void {
        $log = [
            'timestamp' => now(),
            'action' => $action,
            'table' => $table,
            'user_id' => $userId,
            'before' => $beforeData,
            'after' => $afterData,
        ];

        $file = STORAGE_PATH . '/logs/auditoria.log';
        file_put_contents($file, json_encode($log) . PHP_EOL, FILE_APPEND);
    }
}

if (!function_exists('log_auth')) {
    function log_auth(string $action, int $userId, string $ip): void {
        $log = [
            'timestamp' => now(),
            'action' => $action,
            'user_id' => $userId,
            'ip' => $ip,
        ];

        $file = STORAGE_PATH . '/logs/auth.log';
        file_put_contents($file, json_encode($log) . PHP_EOL, FILE_APPEND);
    }
}
