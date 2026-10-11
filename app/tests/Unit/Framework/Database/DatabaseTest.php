<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Database;

use App\Framework\Database\Database;
use App\Framework\Errors\SystemException;
use Override;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
final class DatabaseTest extends TestCase
{
    protected Database $database;

    /** @var MockObject&PDO */
    protected PDO $pdoMock;

    /** @var MockObject&PDOStatement */
    protected PDOStatement $statementMock;

    #[Override]
    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->statementMock = $this->createMock(PDOStatement::class);

        $this->database = new Database(
            [
            'driver' => 'sqlite',
            'host' => 'localhost',
            'port' => 0,
            'database' => ':memory:',
            'user' => 'user',
            'password' => 'pass',
        ],
            function (array $config): MockObject&PDO {
                return $this->pdoMock;
            },
        );
    }

    public function test_the_connection_is_given_the_attributes_the_class_relies_on(): void
    {
        $attributes = [];

        $this->pdoMock->method('setAttribute')
            ->willReturnCallback(function (int $attribute, mixed $value) use (&$attributes): bool {
                $attributes[$attribute] = $value;
                return true;
            });

        $this->pdoMock->method('prepare')->willReturn($this->statementMock);
        $this->statementMock->method('execute')->willReturn(true);

        $this->database->action('DELETE FROM test');

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $attributes[PDO::ATTR_ERRMODE] ?? null);
        $this->assertSame(PDO::FETCH_ASSOC, $attributes[PDO::ATTR_DEFAULT_FETCH_MODE] ?? null);
        $this->assertFalse($attributes[PDO::ATTR_EMULATE_PREPARES] ?? null);
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
