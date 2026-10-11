<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Support;

use App\Framework\Support\File;
use App\Framework\Support\StringGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    /**
     * @return array<non-empty-string, array{non-empty-string}>
     */
    public static function urls_that_are_not_http(): array
    {
        return [
            'Local file'        => ['file:///etc/passwd'],
            'FTP'               => ['ftp://example.com/image.jpg'],
            'Gopher'            => ['gopher://example.com/'],
            'No scheme'         => ['example.com/image.jpg'],
            'Not a URL'         => ['not a url'],
            'HTTP without host' => ['http:///image.jpg'],
        ];
    }

    /**
     * @param non-empty-string $url
     */
    #[DataProvider('urls_that_are_not_http')]
    public function test_download_refuses_a_url_that_is_not_http_or_https(string $url): void
    {
        $target = sys_get_temp_dir() . '/file-test-' . StringGenerator::hex(8);

        $this->assertFalse(File::download($url, $target, true));
        $this->assertFileDoesNotExist($target);
        $this->assertFileDoesNotExist($target . '.part');
    }
}
