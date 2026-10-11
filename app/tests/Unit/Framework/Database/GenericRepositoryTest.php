<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Database;

use App\Framework\Database\Database;
use App\Framework\Database\GenericRepository;
use App\Framework\Database\SqlWhere;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class GenericRepositoryTest extends TestCase
{
    public function test_fetch_rows_applies_the_limit_and_offset(): void
    {
        $sql = self::sqlOf(
            static fn (GenericRepository $repository): array => $repository->fetchRows('id', 'town', 'name = ?', 'Bath', 'name', 10, 20),
        );

        $this->assertMatchesRegularExpression('/LIMIT 10\s+OFFSET 20/', $sql);
    }

    public function test_fetch_rows_with_sql_where_applies_the_limit_and_offset(): void
    {
        $sql = self::sqlOf(
            static fn (GenericRepository $repository): array => $repository->fetchRowsWithSqlWhere('id', 'town', new SqlWhere(), 'name', 10, 20),
        );

        $this->assertMatchesRegularExpression('/LIMIT 10\s+OFFSET 20/', $sql);
    }

    /**
     * The SQL the repository prepares when $query runs
     *
     * @param callable(GenericRepository): mixed $query
     */
    private static function sqlOf(callable $query): string
    {
        $statement = self::createStub(PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn([]);

        $prepared = '';
        $pdo = self::createStub(PDO::class);
        $pdo->method('prepare')->willReturnCallback(
            static function (string $sql) use (&$prepared, $statement): PDOStatement {
                $prepared = $sql;
                return $statement;
            },
        );

        $database = new Database(
            ['driver' => 'pgsql', 'host' => 'localhost', 'port' => 0, 'database' => 'test', 'user' => 'user', 'password' => 'pass'],
            static fn (): PDO => $pdo,
        );

        $query(new GenericRepository($database));

        return $prepared;
    }
}
