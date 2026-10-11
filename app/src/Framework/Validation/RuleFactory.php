<?php

declare(strict_types=1);

namespace App\Framework\Validation;

use App\Framework\Validation\Rules\MaxFloatRule;
use App\Framework\Validation\Rules\MaxIntRule;
use App\Framework\Validation\Rules\MaxStringRule;
use App\Framework\Validation\Rules\MinFloatRule;
use App\Framework\Validation\Rules\MinIntRule;
use App\Framework\Validation\Rules\MinStringRule;
use App\Framework\Validation\Rules\RequiredRule;
use App\Framework\Validation\Rules\RuleInterface;
use App\Validation\Rules\Venue\MightBeExtIdRule;
use InvalidArgumentException;

final class RuleFactory
{
    public static function fromString(string $rule, string $type): RuleInterface
    {
        $parts = explode(':', $rule, 2);
        $name = $parts[0];
        $parameter = $parts[1] ?? null;

        switch (strtolower($name)) {
            case 'required': return new RequiredRule();
            case 'min':
                return match($type) {
                    'string'    => new MinStringRule((int) $parameter),
                    'int'       => new MinIntRule((int) $parameter),
                    'float'     => new MinFloatRule((float) $parameter),
                    default     => throw new InvalidArgumentException("Invalid type ({$type}) for `min`: {$name}"),
                };
            case 'max':
                return match ($type) {
                    'string'    => new MaxStringRule((int) $parameter),
                    'int'       => new MaxIntRule((int) $parameter),
                    'float'     => new MaxFloatRule((float) $parameter),
                    default     => throw new InvalidArgumentException("Invalid type ({$type}) for `min`: {$name}"),
                };
            case 'extid': return new MightBeExtIdRule();
            default: throw new InvalidArgumentException("Unknown rule: {$name}");
        }
    }
}
