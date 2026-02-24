<?php

namespace App\Services;

use App\Core\App;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

final class ProductImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function storeUploadedImage(array $file, ?string $oldPath = null): string
    {
        $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $msg = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu cok buyuk. Maksimum ' . ini_get('upload_max_filesize') . ' yukleyebilirsiniz.',
                UPLOAD_ERR_NO_FILE => 'Lutfen bir fotograf secin.',
                default => 'Gecerli bir fotograf yuklenmedi (hata kodu: ' . $uploadError . ').',
            };
            throw new RuntimeException($msg);
        }

        $tmpPath = $file['tmp_name'] ?? '';
        $isValidUpload = is_uploaded_file($tmpPath) || (PHP_SAPI === 'cli' && is_file($tmpPath));
        if ($tmpPath === '' || !$isValidUpload) {
            throw new RuntimeException('Yuklenen dosya dogrulanamadi.');
        }

        $mime = mime_content_type($tmpPath) ?: '';
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException('Sadece JPG, PNG veya WEBP yukleyebilirsiniz.');
        }

        $config = App::config();
        $relativeDir = trim($config['uploads']['products_dir'], '/\\');
        $absoluteDir = App::basePath($relativeDir);
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $fileName = bin2hex(random_bytes(12)) . '.webp';
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $fileName;

        $image = $this->manager->read($tmpPath);
        $image->scaleDown(width: 1200, height: 1200);
        $image->toWebp(85)->save($absolutePath);

        if ($oldPath !== null && str_starts_with($oldPath, $relativeDir . '/')) {
            $this->deleteImage($oldPath);
        }

        return str_replace('\\', '/', $relativeDir . '/' . $fileName);
    }

    public function deleteImage(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $relativeDir = trim(App::config()['uploads']['products_dir'], '/\\');
        if (!str_starts_with($path, $relativeDir . '/')) {
            return;
        }

        $absolutePath = App::basePath($path);
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }
}
