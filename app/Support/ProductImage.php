<?php

namespace App\Support;

final class ProductImage
{
    public static function listingPath(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return 'assets/img/product/home-one/product-1.jpg';
        }

        if (!preg_match('~^storage/uploads/products/([a-f0-9]{24,32}\.webp)$~i', $path, $matches)) {
            return $path;
        }

        $thumbnail = 'storage/uploads/products/thumbs/' . $matches[1];
        return is_file(dirname(__DIR__, 2) . '/' . $thumbnail) ? $thumbnail : $path;
    }
}
