<?php

namespace App\Services;

use App\Core\App;
use RuntimeException;

final class PaymentProofService
{
    public function storeUploadedProof(array $file): string
    {
        $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $msg = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dekont dosya boyutu cok buyuk.',
                UPLOAD_ERR_NO_FILE => 'Lutfen dekont dosyasi secin.',
                default => 'Dekont yuklenemedi (hata kodu: ' . $uploadError . ').',
            };
            throw new RuntimeException($msg);
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $isValidUpload = is_uploaded_file($tmpPath) || (PHP_SAPI === 'cli' && is_file($tmpPath));
        if ($tmpPath === '' || !$isValidUpload) {
            throw new RuntimeException('Yuklenen dekont dogrulanamadi.');
        }

        $mime = mime_content_type($tmpPath) ?: '';
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];
        if (!isset($allowedMimes[$mime])) {
            throw new RuntimeException('Dekont olarak JPG, PNG, WEBP veya PDF yukleyebilirsiniz.');
        }

        $config = App::config();
        $relativeDir = trim($config['uploads']['payment_proofs_dir'], '/\\');
        $absoluteDir = App::basePath($relativeDir);
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $fileName = bin2hex(random_bytes(12)) . '.' . $allowedMimes[$mime];
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpPath, $absolutePath)) {
            if (!copy($tmpPath, $absolutePath)) {
                throw new RuntimeException('Dekont dosyasi kaydedilemedi.');
            }
        }

        return str_replace('\\', '/', $relativeDir . '/' . $fileName);
    }
}
