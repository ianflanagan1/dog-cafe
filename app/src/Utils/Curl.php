<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * @phpstan-type Headers array<non-empty-string>
 * @phpstan-type Post array<string, ?scalar>
 */
class Curl
{
    /**
     * @param non-empty-string $url
     * @param Headers $headers
     * @param Post $payload
     * @return array<mixed>|false
     */
    public static function arrayResponse(string $url, array $headers = [], array $payload = []): array|false
    {
        $res = self::stringResponse($url, $headers, $payload);

        if ($res === false) {
            return false;
        }

        $res = json_decode($res, true);

        if (!is_array($res)) {
            Log::error('Response is not array', $res);
            return false;
        }

        return $res;
    }

    /**
     * @param non-empty-string $url
     * @param Headers $headers
     * @param Post $payload
     * @return non-empty-string|false
     */
    public static function stringResponse(string $url, array $headers = [], array $payload = []): string|false
    {
        /** @var string|false $res */
        $res = self::curl($url, $headers, $payload);

        if ($res === false) {
            return false;
        }

        if (empty($res)) {
            Log::error('Response invalid', $res);
            return false;
        }

        return $res;
    }

    /**
     * @param non-empty-string $url
     * @param Headers $headers
     * @param Post $post
     * @param ?resource $fileHandle
     * @return bool
     */
    public static function fileResponse(string $url, array $headers = [], array $post = [], mixed $fileHandle = null): bool
    {
        /** @var bool $res */
        $res = Curl::curl($url, $headers, $post, $fileHandle);

        return $res;
    }

    /**
     * @param non-empty-string $url
     * @param Headers $headers
     * @param Post $post
     * @param ?resource $fileHandle
     * @return string|bool Returns string if content is returned, true if written to file (when $fileHandle is provided), or false on error.
     */
    public static function curl(string $url, array $headers = [], array $post = [], mixed $fileHandle = null): string|bool
    {
        $handle = curl_init();

        if ($handle === false) {
            Log::error("curl_init failed for URL: {$url}");
            return false;
        }

        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_USERAGENT       => 'DogCafe/1.0',
            CURLOPT_SSL_VERIFYPEER  => true,    // Validate SSL certificate authenticity
            CURLOPT_SSL_VERIFYHOST  => 2,       // Confirm URL's hostname matches SSL certificate
            CURLOPT_FAILONERROR     => false,   // Don't HTTP status >= 400 as failure
            CURLOPT_FOLLOWLOCATION  => true,    // Follow redirects
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HEADER          => 0,       // Output only contains body
        ];

        if (!empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        if (!empty($post)) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($post);
        }

        // Response is written to disk (return value is true (success) or false (failure))
        if ($fileHandle !== null) {
            $options[CURLOPT_FILE] = $fileHandle;

            // Response is returned by this method as string, or false (failure)
        } else {
            $options[CURLOPT_RETURNTRANSFER] = true;
        }

        try {
            curl_setopt_array($handle, $options);

            $res = curl_exec($handle);

            if ($res === false) {
                Log::error("curl_exec failed for {$url}: " . curl_error($handle));
                return false;
            }

            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            if ($httpCode >= 400) {
                Log::error("Download returned HTTP {$httpCode} for {$url}", $res);
                return false;
            }

            return $res;

        } finally {
            curl_close($handle);
        }
    }
}
