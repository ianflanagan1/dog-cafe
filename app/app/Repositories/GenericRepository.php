<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use App\Database\SqlWhere;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type NonNegInt from StandardTypes
 */
class GenericRepository
{
    public function __construct(
        protected Database $database,
    ) {
    }

    /**
     * @param string $orderBy
     * @return string
     */
    public static function getOrderByString(string $orderBy = ''): string
    {
        return $orderBy !== ''
            ? "ORDER BY {$orderBy}"
            : '';
    }

    /**
     * @param NonNegInt $limit
     * @return string
     */
    public static function getLimitString(int $limit = 0): string
    {
        return $limit > 0
            ? "LIMIT {$limit}"
            : '';
    }

    /**
     * @param NonNegInt $offset
     * @return string
     */
    public static function getOffsetString(int $offset = 0): string
    {
        return $offset > 0
            ? "OFFSET {$offset}"
            : '';
    }

    /**
     * @param NonNegInt $totalPages
     * @param PosInt $page
     * @return bool
     */
    public static function pageHasItems(int $totalPages, int $page): bool
    {
        return $totalPages > 0 && $page <= $totalPages;
    }

    /**
     * @param non-empty-string $columns
     * @param non-empty-string $table
     * @param non-empty-string $where
     * @param ?scalar $parameter
     * @return ?array<string, ?scalar>
     */
    public function findRow(string $columns, string $table, string $where, bool|float|int|string|null $parameter): ?array
    {
        /** @var ?array<string, ?scalar> $res */

        $res = $this->database->fetch(
            "SELECT {$columns}
            FROM {$table}
            WHERE {$where} = ?
            LIMIT 1",
            [$parameter],
        );

        return $res;
    }

    /**
     * @param non-empty-string $selectColumn
     * @param non-empty-string $table
     * @param non-empty-string $where
     * @param ?scalar $parameter
     * @return ?scalar
     */
    public function findValue(string $selectColumn, string $table, string $where, bool|float|int|string|null $parameter): bool|float|int|string|null
    {
        /** @var ?scalar $res */

        $res = $this->database->fetchColumn(
            "SELECT {$selectColumn}
            FROM {$table}
            WHERE {$where} = ?
            LIMIT 1",
            [$parameter],
        );

        return $res;
    }

    /**
     * @param non-empty-string $selectColumn
     * @param non-empty-string $table
     * @param non-empty-string $where
     * @param ?scalar $parameter
     * @return list<?scalar>
     */
    public function fetchValues(string $selectColumn, string $table, string $where, bool|float|int|string|null $parameter): array
    {
        /** @var list<?scalar> $res */

        $res = $this->database->fetchAllColumn(
            "SELECT {$selectColumn}
            FROM {$table}
            WHERE {$where} = ?",
            [$parameter],
        );

        return $res;
    }

    /**
     * @param non-empty-string $columns
     * @param non-empty-string $table
     * @param non-empty-string $where
     * @param ?scalar $parameter
     * @param string $orderBy
     * @param NonNegInt $limit
     * @param NonNegInt $offset
     * @return list<array<string, ?scalar>>
     */
    public function fetchRows(
        string $columns,
        string $table,
        string $where,
        bool|float|int|string|null $parameter,
        string $orderBy = '',
        int $limit = 0,
        int $offset = 0,
    ): array {
        $orderBy = self::getOrderByString($orderBy);
        $limit = self::getLimitString($limit);
        $offset = self::getOffsetString($offset);

        /** @var list<array<string, ?scalar>> $res */

        $res = $this->database->fetchAll(
            "SELECT {$columns}
            FROM {$table}
            WHERE {$where}
            {$orderBy}
            {$limit}",
            [$parameter],
        );

        return $res;
    }

    /**
     * @param non-empty-string $columns
     * @param non-empty-string $table
     * @param SqlWhere $where
     * @return ?array<string, ?scalar>
     */
    public function findRowWithSqlWhere(string $columns, string $table, SqlWhere $where): ?array
    {
        /** @var ?array<string, ?scalar> $res */

        $res = $this->database->fetch(
            "SELECT {$columns}
            FROM {$table}
            {$where}
            LIMIT 1",
            $where->getParameters(),
        );

        return $res;
    }

    /**
     * @param non-empty-string $columns
     * @param non-empty-string $table
     * @param SqlWhere $where
     * @param string $orderBy
     * @param NonNegInt $limit
     * @param NonNegInt $offset
     * @return list<array<string, ?scalar>>
     */
    public function fetchRowsWithSqlWhere(
        string $columns,
        string $table,
        SqlWhere $where,
        string $orderBy = '',
        int $limit = 0,
        int $offset = 0,
    ): array {
        $orderBy = self::getOrderByString($orderBy);
        $limit = self::getLimitString($limit);
        $offset = self::getOffsetString($offset);

        /** @var list<array<string, ?scalar>> $res */

        $res = $this->database->fetchAll(
            "SELECT {$columns}
            FROM {$table}
            {$where}
            {$orderBy}
            {$limit}
            {$offset}",
            $where->getParameters(),
        );

        return $res;
    }

    /**
     * @param SqlWhere $sqlWhere
     * @param string $table
     * @return NonNegInt
     */
    public function getTotal(SqlWhere $sqlWhere, string $table): int
    {
        $res = $this->database->fetchColumn(
            "SELECT COUNT(id)
            FROM {$table}
            {$sqlWhere->getString()}",
            $sqlWhere->getParameters()
        );

        assert(is_int($res) && $res >= 0, 'Total must be non-negative integer');

        return $res;
    }

    /**
     * @param SqlWhere $sqlWhere
     * @param PosInt $pageSize
     * @return NonNegInt
     */
    public function getTotalPages(SqlWhere $sqlWhere, int $pageSize): int
    {
        $total = $this->getTotal($sqlWhere, 'venue');

        /** @phpstan-var NonNegInt $totalPages */
        $totalPages = (int) ceil($total / $pageSize);

        return $totalPages;
    }
}
