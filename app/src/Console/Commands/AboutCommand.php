<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Framework\Config\Config;
use App\Framework\Console\CommandInterface;
use App\Framework\Console\Output;
use Override;

/**
 * Shows what this app is running with, e.g. to check which environment and php.ini files a container uses
 */
final readonly class AboutCommand implements CommandInterface
{
    public function __construct(private Config $config)
    {
    }

    #[Override]
    public static function description(): string
    {
        return 'Show the environment, PHP version and php.ini files this app runs with';
    }

    #[Override]
    public function run(array $args, Output $output): int
    {
        if ($args !== []) {
            $output->error('about takes no arguments');
            return self::INVALID;
        }

        /**
         * @var array{
         *      app_env: non-empty-string,
         *      url: non-empty-string,
         * }
         */
        $appConfig = $this->config->get('app');

        $iniFile = php_ini_loaded_file();
        $scannedIniFiles = php_ini_scanned_files();

        $output->line('Environment:  ' . $appConfig['app_env']);
        $output->line('URL:          ' . $appConfig['url']);
        $output->line('PHP:          ' . PHP_VERSION);
        $output->line('php.ini:      ' . ($iniFile === false ? '(none)' : $iniFile));
        $output->line('Other ini:    ' . ($scannedIniFiles === false ? '(none)' : str_replace(",\n", ', ', trim($scannedIniFiles))));

        return self::SUCCESS;
    }
}
