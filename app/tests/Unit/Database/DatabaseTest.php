<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Database\Database;
use App\Exceptions\SystemException;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseTest extends TestCase
{
    protected Database $database;

    /** @var MockObject&PDO */
    protected PDO $pdoMock;

    /** @var MockObject&PDOStatement */
    protected PDOStatement $statementMock;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->statementMock = $this->createMock(PDOStatement::class);

        $this->database = new Database([
            'driver' => 'sqlite',
            'host' => 'localhost',
            'port' => 0,
            'database' => ':memory:',
            'user' => 'user',
            'password' => 'pass',
        ],
            function (array $config, array $defaultOptions): MockObject&PDO {
                return $this->pdoMock;
            },
        );
    }

    public function test_action_returns_row_count(): void
    {
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with('DELETE FROM test WHERE id = ?')
            ->willReturn($this->statementMock);

        $this->statementMock->expects($this->once())
            ->method('execute')
            ->with([1])
            ->willReturn(true);

        $this->statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(3);

        $count = $this->database->action('DELETE FROM test WHERE id = ?', [1]);

        $this->assertSame(3, $count);
    }

    public function test_fetch_returns_row_array(): void
    {
        $this->pdoMock->method('prepare')->willReturn($this->statementMock);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetch')->willReturn(['id' => 1, 'name' => 'test']);

        $result = $this->database->fetch('SELECT * FROM test');

        $this->assertIsArray($result);
        $this->assertSame(['id' => 1, 'name' => 'test'], $result);
    }

    public function test_fetch_returns_null_if_no_result(): void
    {
        $this->pdoMock->method('prepare')->willReturn($this->statementMock);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetch')->willReturn(false);

        $result = $this->database->fetch('SELECT * FROM test');

        $this->assertNull($result);
    }

    public function test_fetch_all_returns_rows(): void
    {
        $this->pdoMock->method('prepare')->willReturn($this->statementMock);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetchAll')->willReturn([
            ['id' => 1],
            ['id' => 2],
        ]);

        $result = $this->database->fetchAll('SELECT * FROM test');

        $this->assertCount(2, $result);
    }

    public function test_fetch_column_returns_value(): void
    {
        $this->pdoMock->method('prepare')->willReturn($this->statementMock);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetchColumn')->willReturn('value');

        $result = $this->database->fetchColumn('SELECT col FROM test');

        $this->assertSame('value', $result);
    }

    public function test_fetch_all_column_return_array(): void
    {
        $this->pdoMock->method('prepare')->willReturn($this->statementMock);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetchAll')->willReturn(['a', 'b']);

        $result = $this->database->fetchAllColumn('SELECT col FROM test');

        $this->assertSame(['a', 'b'], $result);
    }

    public function test_insert_returns_id(): void
    {
        $this->pdoMock->method('prepare')->willReturn($this->statementMock);
        $this->pdoMock->method('lastInsertId')->willReturn('42');
        $this->statementMock->method('execute')->willReturn(true);

        $id = $this->database->insert('INSERT INTO test (name) VALUES (?)', ['foo']);

        $this->assertSame(42, $id);
    }

    public function test_action_throws_system_exception_on_pdo_exception(): void
    {
        $this->pdoMock->method('prepare')->willThrowException(new PDOException('fail'));

        $this->expectException(SystemException::class);

        $this->database->action('DELETE FROM test');
    }

    public function test_insert_throws_runtime_exception_on_empty_id(): void
    {
        $this->pdoMock->method('prepare')->willReturn($this->statementMock);
        $this->statementMock->method('execute')->willReturn(true);
        $this->pdoMock->method('lastInsertId')->willReturn('');

        $this->expectException(RuntimeException::class);

        $this->database->insert('INSERT INTO test (name) VALUES (?)', ['foo']);
    }
}
