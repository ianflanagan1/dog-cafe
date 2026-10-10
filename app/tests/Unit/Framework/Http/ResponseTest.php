<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Http;

use App\Framework\Errors\ErrorCode;
use App\Framework\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function test_json_wraps_content_in_data(): void
    {
        $response = Response::json(201, ['a' => 1]);

        $this->assertSame('{"data":{"a":1}}', $response->body);
        $this->assertSame(201, $response->status);
        $this->assertSame('application/json; charset=utf-8', $response->headers['content-type']);
    }

    public function test_json_error_wraps_errors_in_data(): void
    {
        $response = Response::jsonError(404, ErrorCode::ItemNotFound);

        /** @var array{data: array{errors: list<array{code: int}>}} $decoded */
        $decoded = json_decode($response->body, true);

        $this->assertSame(404, $response->status);
        $this->assertSame(ErrorCode::ItemNotFound->value, $decoded['data']['errors'][0]['code']);
    }

    public function test_redirect_sets_location(): void
    {
        $response = Response::redirect(303, '/login');

        $this->assertSame(303, $response->status);
        $this->assertSame(['location' => '/login'], $response->headers);
        $this->assertSame('', $response->body);
    }

    public function test_no_content(): void
    {
        $response = Response::noContent();

        $this->assertSame(204, $response->status);
        $this->assertSame('', $response->body);
    }

    public function test_with_header_returns_new_instance_with_lowercase_name(): void
    {
        $original = new Response(200, [], 'body');
        $modified = $original->withHeader('Cache-Control', 'no-store');

        $this->assertSame([], $original->headers);
        $this->assertSame(['cache-control' => 'no-store'], $modified->headers);
        $this->assertSame('body', $modified->body);
    }
}
