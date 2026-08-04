<?php

declare(strict_types=1);

if (!function_exists('money')) {
    function money(float $value, string $currency = 'R$'): string {
        return $currency . ' ' . number_format($value, 2, ',', '.');
    }
}

if (!function_exists('to_money')) {
    function to_money(string $value): float {
        return (float) str_replace(['.', ','], ['', '.'], $value);
    }
}

if (!function_exists('date_br')) {
    function date_br(string $date, string $format = 'd/m/Y'): string {
        if (!$date) return '';

        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt ? $dt->format($format) : $date;
    }
}

if (!function_exists('datetime_br')) {
    function datetime_br(string $datetime, string $format = 'd/m/Y H:i'): string {
        if (!$datetime) return '';

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $dt ? $dt->format($format) : $datetime;
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string {
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) return "$diff segundos atrás";
        if ($diff < 3600) return floor($diff / 60) . " minutos atrás";
        if ($diff < 86400) return floor($diff / 3600) . " horas atrás";
        if ($diff < 604800) return floor($diff / 86400) . " dias atrás";

        return date_br($datetime);
    }
}

if (!function_exists('truncate')) {
    function truncate(string $text, int $length = 100): string {
        if (strlen($text) <= $length) return $text;
        return substr($text, 0, $length) . '...';
    }
}

if (!function_exists('slug')) {
    function slug(string $text): string {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}

if (!function_exists('percentage')) {
    function percentage(float $value, float $total, int $decimals = 2): string {
        if ($total === 0) return '0.00%';
        return number_format(($value / $total) * 100, $decimals, ',', '.') . '%';
    }
}

if (!function_exists('plural')) {
    function plural(int $count, string $singular, string $plural): string {
        return $count === 1 ? $singular : $plural;
    }
}

if (!function_exists('sanitize')) {
    function sanitize(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
