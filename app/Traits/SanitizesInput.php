<?php

namespace App\Traits;

/**
 * Input Sanitization Trait
 * Untuk membersihkan input dari karakter berbahaya
 */
trait SanitizesInput
{
    /**
     * Sanitize string input
     * Menghapus HTML tags dan karakter berbahaya
     */
    protected function sanitizeString(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove HTML tags
        $input = strip_tags($input);
        
        // Remove extra whitespace
        $input = preg_replace('/\s+/', ' ', $input);
        
        // Trim
        $input = trim($input);
        
        return $input === '' ? null : $input;
    }

    /**
     * Sanitize search input
     * Khusus untuk search query, escape karakter SQL wildcard
     */
    protected function sanitizeSearch(?string $search): string
    {
        if ($search === null || $search === '') {
            return '';
        }

        // Remove HTML tags
        $search = strip_tags($search);
        
        // Remove extra whitespace
        $search = preg_replace('/\s+/', ' ', $search);
        
        // Trim
        $search = trim($search);
        
        // Escape SQL wildcard characters untuk prevent injection
        // Laravel sudah handle SQL injection via parameter binding,
        // tapi ini extra layer untuk wildcard abuse
        $search = str_replace(['%', '_'], ['\%', '\_'], $search);
        
        return $search;
    }

    /**
     * Sanitize array of strings
     */
    protected function sanitizeArray(array $input): array
    {
        return array_map(fn($item) => $this->sanitizeString($item), $input);
    }

    /**
     * Sanitize numeric input
     * Pastikan input adalah angka valid
     */
    protected function sanitizeNumeric(mixed $input, int $default = 0): int
    {
        if ($input === null || $input === '') {
            return $default;
        }

        $numeric = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        return (int) $numeric;
    }

    /**
     * Sanitize decimal input
     */
    protected function sanitizeDecimal(mixed $input, float $default = 0.0): float
    {
        if ($input === null || $input === '') {
            return $default;
        }

        $numeric = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        return (float) $numeric;
    }

    /**
     * Sanitize boolean input
     */
    protected function sanitizeBoolean(mixed $input, bool $default = false): bool
    {
        if ($input === null || $input === '') {
            return $default;
        }

        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Sanitize email input
     */
    protected function sanitizeEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Sanitize URL input
     */
    protected function sanitizeUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $url = filter_var($url, FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
