<?php

declare(strict_types=1);

namespace App\Framework\Config;

use SensitiveParameter;

final readonly class Config
{
    /**
     * @param array<string, array<string, ?scalar>> $config
     */
    public function __construct(#[SensitiveParameter] private array $config)
    {
    }

    /**
     * @param string $index
     * @return array<string, ?scalar>
     */
    public function get(string $index): array
    {
        if (!isset($this->config[$index])) {
            throw new ConfigException("Config not found: `$index`");
        }

        return $this->config[$index];
    }
}
