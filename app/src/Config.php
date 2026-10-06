<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\ConfigException;

readonly class Config
{
    /**
     * @param array<string, array<string, ?scalar>> $config
     */
    public function __construct(protected array $config)
    {
    }

    /**
     * @param string $index
     * @return array<string, ?scalar>
     * @throws ConfigException
     */
    public function get(string $index): array
    {
        if (!isset($this->config[$index])) {
            throw new ConfigException("Config not found: `$index`");
        }

        return $this->config[$index];
    }
}
