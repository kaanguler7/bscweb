<?php

namespace App\Support;

final class ProductSeo
{
    private const SITE_SUFFIX = ' | BSC Elektronik';
    private const MAX_TITLE_LENGTH = 70;
    private const MAX_DESCRIPTION_LENGTH = 160;

    public static function primaryCode(array $product): string
    {
        $technical = isset($product['technical']) && is_array($product['technical']) ? $product['technical'] : [];

        return ProductCode::firstIdentifier(
            $product['part_code'] ?? null,
            $technical['supplier_code'] ?? null,
            $technical['part_number'] ?? null
        ) ?? '';
    }

    public static function displayName(array $product): string
    {
        $name = self::clean($product['name'] ?? '');
        $code = self::primaryCode($product);

        if ($name === '') {
            return $code;
        }
        if ($code === '' || mb_stripos($name, $code, 0, 'UTF-8') === 0) {
            return $name;
        }

        return $code . ', ' . $name;
    }

    public static function pageTitle(array $product): string
    {
        $parts = self::uniqueNonEmpty([
            self::primaryCode($product),
            $product['brand'] ?? '',
            $product['model'] ?? '',
            $product['category_name'] ?? '',
        ]);

        $base = implode(' ', $parts);
        if ($base === '' || count($parts) < 2) {
            $base = self::displayName($product);
        }
        if ($base === '') {
            $base = 'TV Yedek Parça';
        }

        $available = self::MAX_TITLE_LENGTH - mb_strlen(self::SITE_SUFFIX, 'UTF-8');
        if (mb_strlen($base, 'UTF-8') > $available) {
            $base = rtrim(mb_substr($base, 0, $available - 1, 'UTF-8')) . '…';
        }

        return $base . self::SITE_SUFFIX;
    }

    public static function metaDescription(array $product): string
    {
        $productDescription = trim(preg_replace('/\s+/u', ' ', (string) ($product['description'] ?? '')) ?? '');
        if ($productDescription !== '') {
            return mb_strlen($productDescription, 'UTF-8') > self::MAX_DESCRIPTION_LENGTH
                ? rtrim(mb_substr($productDescription, 0, self::MAX_DESCRIPTION_LENGTH - 3, 'UTF-8')) . '...'
                : $productDescription;
        }

        $description = self::displayName($product);
        $context = self::uniqueNonEmpty([
            $product['brand'] ?? '',
            $product['model'] ?? '',
            $product['category_name'] ?? '',
        ]);

        foreach ($context as $value) {
            if ($description !== '' && mb_stripos($description, $value, 0, 'UTF-8') === false) {
                $description .= ' - ' . $value;
            }
        }

        $description .= '. Ürün fotoğrafı, stok ve fiyat bilgilerini BSC Elektronik\'te inceleyin.';
        if (mb_strlen($description, 'UTF-8') > self::MAX_DESCRIPTION_LENGTH) {
            $description = rtrim(mb_substr($description, 0, self::MAX_DESCRIPTION_LENGTH - 3, 'UTF-8')) . '...';
        }

        return trim($description);
    }

    /**
     * @return array<int, string>
     */
    public static function codes(array $product): array
    {
        $technical = isset($product['technical']) && is_array($product['technical']) ? $product['technical'] : [];
        $candidates = [
            self::primaryCode($product),
        ];

        foreach (['part_number', 'supplier_code', 'tv_panel_model', 'known_brand_model'] as $key) {
            $candidates[] = $technical[$key] ?? '';
        }
        $candidates[] = $product['model'] ?? '';

        return array_slice(self::uniqueNonEmpty($candidates), 0, 24);
    }

    /**
     * @param array<int, mixed> $values
     * @return array<int, string>
     */
    private static function uniqueNonEmpty(array $values): array
    {
        $result = [];
        $seen = [];
        foreach ($values as $value) {
            $value = self::clean($value);
            if ($value === '' || mb_strlen($value, 'UTF-8') < 2) {
                continue;
            }

            $key = mb_strtolower($value, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $value;
        }

        return $result;
    }

    private static function clean(mixed $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $value === null ? '' : trim($value);
    }
}
