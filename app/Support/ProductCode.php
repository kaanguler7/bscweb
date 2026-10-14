<?php

namespace App\Support;

use RuntimeException;

final class ProductCode
{
    public const MAX_LENGTH = 120;

    public static function normalize(mixed $value): string
    {
        $code = trim((string) $value);
        $code = strtr($code, [
            "\u{2010}" => '-',
            "\u{2011}" => '-',
            "\u{2012}" => '-',
            "\u{2013}" => '-',
            "\u{2014}" => '-',
            "\u{2212}" => '-',
        ]);
        $code = preg_replace('/\s+/u', ' ', $code);

        return $code === null ? '' : trim($code);
    }

    public static function requireValid(mixed $value): string
    {
        $code = self::normalize($value);
        $length = mb_strlen($code, 'UTF-8');

        if ($code === '') {
            throw new RuntimeException('Parça kodu zorunludur.');
        }
        if ($length < 2 || $length > self::MAX_LENGTH) {
            throw new RuntimeException('Parça kodu 2 ile ' . self::MAX_LENGTH . ' karakter arasında olmalıdır.');
        }
        if (preg_match('/[\x00-\x1F\x7F<>,;|"\'`]/u', $code) === 1) {
            throw new RuntimeException('Parça koduna tek bir ana kod girin; virgül, noktalı virgül veya özel HTML karakterleri kullanmayın.');
        }
        if (preg_match('/[\p{L}\p{N}]/u', $code) !== 1) {
            throw new RuntimeException('Parça kodu en az bir harf veya rakam içermelidir.');
        }

        return $code;
    }

    public static function firstIdentifier(mixed ...$candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $candidate = self::normalize($candidate);
            if ($candidate === '') {
                continue;
            }

            $segments = preg_split('/\s*[,;|]+\s*/u', $candidate) ?: [];
            foreach ($segments as $segment) {
                $identifier = self::normalize($segment);
                if ($identifier !== '') {
                    return mb_substr($identifier, 0, self::MAX_LENGTH, 'UTF-8');
                }
            }
        }

        return null;
    }
}
