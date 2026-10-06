<?php

declare(strict_types=1);

namespace App\Database;

use App\Exceptions\SystemException;
use App\Types\StandardTypes;
use App\Utils\Log;
use PDO;
use PDOException;
use RuntimeException;
use stdClass;

/**
 * @mixin PDO
 *
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type NonNegInt from StandardTypes
 * */
class Database
{
    protected const array DEFAULT_OPTIONS = [
        PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES      => false,
        PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
    ];
    protected ?PDO $pdo = null;

    /** @var callable */
    protected $pdoFactory;

    /**
     * @param array{
     *      driver: string,
     *      port: int,
     *      host: string,
     *      database: string,
     *      user: string,
     *      password: string,
     *      options?: list<int|bool>
     * } $config
     * @param callable $pdoFactory
     */
    public function __construct(
        protected array $config,
        callable $pdoFactory,
    ) {
        $this->pdoFactory = $pdoFactory;
    }

    /**
     * Execute an action query (UPDATE/DELETE) and return affected row count.
     *
     * @param non-empty-string $sql
     * @param array<string|int, mixed> $values
     * @return NonNegInt
     */
    public function action(string $sql, array $values = []): int
    {
        try {
            $statement = $this->connect()->prepare($sql);
            $executed = $statement->execute($values);

            if ($executed === false) {
                throw new RuntimeException("Failed to execute action query");
            }

            $rows = $statement->rowCount();

        } catch (PDOException $e) {
            self::error($e, $sql);
        }

        return $rows;
    }

    /**
     * Fetch a single row (or false if none). Caller must handle false if expecting possible absence.
     *
     * @param non-empty-string $sql
     * @param array<string|int, mixed> $values
     * @param NonNegInt $mode
     * @return array<string|int, mixed>|stdClass|null
     */
    public function fetch(string $sql, array $values = [], int $mode = PDO::FETCH_ASSOC): array|stdClass|null
    {
        try {
            $statement = $this->connect()->prepare($sql);
            $executed = $statement->execute($values);

            if ($executed === false) {
                throw new RuntimeException("Failed to execute fetch query");
            }

            $res = $statement->fetch($mode);

        } catch (PDOException $e) {
            self::error($e, $sql);
        }

        if ($res === false) {
            return null;
        }

        assert(
            is_array($res) || $res instanceof stdClass,
            '$res must be an array or an instance of stdClass'
        );

        /** @var array<string|int, mixed>|stdClass $res */

        return $res;
    }

    /**
     * Fetch all rows; guaranteed to return an array (empty if no rows).
     *
     * @param non-empty-string $sql
     * @param array<string|int, mixed> $values
     * @param NonNegInt $mode
     * @return list<array<string|int, mixed>>|list<stdClass>|array<mixed, list<mixed>>
     */
    public function fetchAll(string $sql, array $values = [], int $mode = PDO::FETCH_ASSOC): array
    {
        try {
            $statement = $this->connect()->prepare($sql);
            $executed = $statement->execute($values);

            if ($executed === false) {
                throw new RuntimeException("Failed to execute fetchAll query");
            }

            $res = $statement->fetchAll($mode);

        } catch (PDOException $e) {
            self::error($e, $sql);
        }

        return $res;
    }

    /**
     * Fetch a single column from the next row of the result set (or false if none)
     *
     * @param non-empty-string $sql
     * @param array<string|int, mixed> $values
     * @param NonNegInt $column
     * @return mixed
     */
    public function fetchColumn(string $sql, array $values = [], int $column = 0): mixed
    {
        try {
            $statement = $this->connect()->prepare($sql);
            $executed = $statement->execute($values);

            if ($executed === false) {
                throw new RuntimeException("Failed to execute fetchColumn query");
            }

            $res = $statement->fetchColumn($column);

        } catch (PDOException $e) {
            self::error($e, $sql);
        }

        return $res;
    }

    /**
     * Fetch a single column from all rows as a flat array.
     *
     * @param non-empty-string $sql
     * @param array<string|int, mixed> $values
     * @param NonNegInt $column
     * @return list<mixed>
     */
    public function fetchAllColumn(string $sql, array $values = [], int $column = 0): array
    {
        try {
            $statement = $this->connect()->prepare($sql);
            $executed = $statement->execute($values);

            if ($executed === false) {
                throw new RuntimeException("Failed to execute fetchAllColumn query");
            }

            $res = $statement->fetchAll(PDO::FETCH_COLUMN, $column);

        } catch (PDOException $e) {
            self::error($e, $sql);
        }

        return $res;
    }

    /**
     * Insert and return last insert ID as int; throws on failure.
     *
     * @param non-empty-string $sql
     * @param array<string|int, mixed> $values
     * @return PosInt
     */
    public function insert(string $sql, array $values = []): int
    {
        try {
            $statement = $this->connect()->prepare($sql);
            $executed = $statement->execute($values);

            if ($executed === false) {
                throw new RuntimeException("Failed to execute insert query");
            }

            $id = $this->connect()->lastInsertId();

        } catch (PDOException $e) {
            self::error($e, $sql);
        }

        if ($id === '' || $id === false) {
            throw new RuntimeException("Insert did not produce an ID");
        }

        assert(ctype_digit($id), 'ID must be an integer');

        $id = (int) $id;

        assert($id > 0, 'ID must be positive');

        return $id;
    }

    protected function connect(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = ($this->pdoFactory)($this->config, self::DEFAULT_OPTIONS);
        }

        return $this->pdo;
    }

    /**
     * @param PDOException $e
     * @param non-empty-string $sql
     * @return never
     */
    protected static function error(PDOException $e, string $sql): never
    {
        // Note: Don't Log bound values (potentially sensitive)
        Log::error('Database error:' . $e->getMessage() . ' - ' . $sql);
        throw new SystemException();
    }
}
