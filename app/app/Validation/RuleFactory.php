<?php

declare(strict_types=1);

namespace App\Validation;

use App\Validation\Rules\MaxFloatRule;
use App\Validation\Rules\MaxIntRule;
use App\Validation\Rules\MaxStringRule;
use App\Validation\Rules\MinFloatRule;
use App\Validation\Rules\MinIntRule;
use App\Validation\Rules\MinStringRule;
use App\Validation\Rules\RequiredRule;
use App\Validation\Rules\RuleInterface;
use App\Validation\Rules\Venue\MightBeExtIdRule;
use InvalidArgumentException;

class RuleFactory
{
    public static function fromString(string $rule, string $type): RuleInterface
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

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
