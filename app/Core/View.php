<?php

namespace App\Core;

final class View
{
    public static function render(string $view, array $data, array $config): void
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            http_response_code(404);
            echo 'View bulunamadi: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($data, EXTR_SKIP);
        $pageTitle = $data['pageTitle'] ?? $config['name'];
        $flash = $_SESSION['flash'] ?? null;
        $csrfToken = Csrf::token();
        unset($_SESSION['flash']);
        $isAdminView = str_starts_with($view, 'admin/');
        $headerFile = __DIR__ . '/../Views/' . ($isAdminView ? 'admin/layouts/header.php' : 'layouts/header.php');
        $footerFile = __DIR__ . '/../Views/' . ($isAdminView ? 'admin/layouts/footer.php' : 'layouts/footer.php');

        require $headerFile;
        require $viewFile;
        require $footerFile;
    }
}
