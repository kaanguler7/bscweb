<?php

namespace App\Core;

abstract class Controller
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data, $this->config);
    }

    protected function applicationBaseUrl(): string
    {
        $configured = trim((string) ($this->config['app_url'] ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
        $scheme = $isHttps ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $basePath = trim(dirname($scriptName), '/');
        if ($basePath === '.' || $basePath === '/') {
            $basePath = '';
        }

        return $scheme . '://' . $host . ($basePath !== '' ? '/' . $basePath : '');
    }

    /**
     * @param array<string, scalar> $params
     */
    protected function absoluteRouteUrl(string $route, array $params = []): string
    {
        $query = ['route' => $route];
        foreach ($params as $key => $value) {
            $query[$key] = (string) $value;
        }

        return $this->applicationBaseUrl() . '/index.php?' . http_build_query($query);
    }

    protected function redirect(string $route): void
    {
        header('Location: index.php?route=' . $route);
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}
