<?php

declare(strict_types=1);

namespace App\Validation;

use App\DTOs\Error;
use App\Exceptions\InputValidationException;
use App\Types\StandardTypes;

/**
 * Class to handle filtering and validation of input data according to expected input definitions.
 *
 * This class separates concerns into two main steps:
 * 1. Filtering: Sanitizes and coerces raw input values using PHP's filter_var with specified filter types, options,
 *    and default values. Filtering normalizes inputs before validation, ensuring consistent types.
 *
 * 2. Validation: Applies business or domain validation rules to filtered values. Validation rules are expressed as
 *    strings (e.g. "required", "min:5") and converted to RuleInterface objects by RuleFactory. On validation failure,
 *    collects errors and throws InputValidationException.
 *
 * @phpstan-import-type Inputs from StandardTypes
 * @phpstan-import-type Validated from StandardTypes
 *
 * @phpstan-type ExpectedInputs array<
 *      int|non-empty-string,
 *      non-empty-string|array{
 *          filter?: array{
 *              type?: int,
 *              options?: mixed,
 *              default?: mixed,
 *          },
 *          rules?: list<non-empty-string>
 *      }
 * >
 */
class Validator
{
    /**
     * Filter and validate input data against expected inputs.
     *
     * This method first applies filtering (using filter_var and defaults) on each field, then validates filtered values
     * against the provided rules.
     *
     * @param Inputs $inputs Raw inputs i.e. $_GET, $_POST
     * @param ExpectedInputs $expectedInputs Definitions of expected fields with filter settings and validation rules to apply
     * @return Validated
     */
    public static function handle(array $inputs, array $expectedInputs): array
    {
        $errors = [];
        $validated = [];

        foreach ($expectedInputs as $field => $properties) {

            // If indexed/non-associative, the value is the field name and there no properties
            if (is_int($field) && is_string($properties)) {
                $field = $properties;
                $properties = [];

            // Otherwise, if properties is not an array, convert to empty array
            } elseif (!is_array($properties)) {
                $properties = [];
            }

            /** @phpstan-var non-empty-string $field */

            $value = self::filter(
                $inputs,
                $field,
                $properties['filter']['type'] ?? FILTER_DEFAULT,
                $properties['filter']['options'] ?? null,
                $properties['filter']['default'] ?? null,
            );

            $type = get_debug_type($value);

            self::validate(
                $errors,
                $field,
                $value,
                $properties['rules'] ?? null,
                $type,
            );

            $validated[$field] = $value;
        }

        if (! empty($errors)) {
            throw new InputValidationException($inputs, $errors);
        }

        return $validated;
    }

    /**
     * @param Inputs $inputs
     * @param string $field
     * @param int $type
     * @param mixed $options
     * @param mixed $default
     * @return mixed
     */
    protected static function filter(array $inputs, string $field, int $type, mixed $options, mixed $default): mixed
    {
        if (!isset($inputs[$field])) {
            return $default;
        }

        $value = filter_var(
            $inputs[$field],
            $type,
            ['options' => $options],
        );

        if (!$value) {
            return $default;
        }

        return $value;
    }

    /**
     * @param list<Error> $errors
     * @param string $field
     * @param mixed $value
     * @param ?list<non-empty-string> $rules
     * @return void
     */
    protected static function validate(array &$errors, string $field, mixed $value, ?array $rules, string $type): void
    {
        if ($rules === null) {
            return;
        }

        foreach ($rules as $rule) {
            $object = RuleFactory::fromString($rule, $type);
            $error = $object->validate($field, $value);

            if ($error !== null) {
                $errors[] = $error;
                break; // stop on first failure per field
            }
        }
    }
}
