<?php

declare(strict_types=1);

namespace App\Framework\Cache;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInvalidArgumentException;

final class InvalidKeyException extends InvalidArgumentException implements SimpleCacheInvalidArgumentException
{
}
