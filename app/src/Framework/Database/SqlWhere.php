<?php

declare(strict_types=1);

namespace App\Framework\Database;

final class SqlWhere
{
    /**
     * @param list<non-empty-string> $statements
     * @param list<?scalar> $parameters
     */
    public function __construct(
        private array $statements = [],
        private array $parameters = [],
    ) {
    }

    public function __toString(): string
    {
        return $this->getString();
    }

    /**
     * @param non-empty-string $statement
     * @return void
     */
    public function addStatement(string $statement): void
    {
        $this->statements[] = $statement;
    }

    /**
     * @param ?scalar $parameter
     * @return void
     */
    public function addParameter(bool|float|int|string|null $parameter): void
    {
        $this->parameters[] = $parameter;
    }

    public function getString(): string
    {
        if (empty($this->statements)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $this->statements);
    }

    /**
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
