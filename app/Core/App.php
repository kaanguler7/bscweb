<?php

namespace App\Core;

final class App
{
    private static array $config = [];

    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    public static function config(): array
    {
        return self::$config;
    }

    public static function basePath(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        if ($path === '') {
            return $base;
        }

        return $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}
