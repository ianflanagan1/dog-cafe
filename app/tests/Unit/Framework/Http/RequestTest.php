<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Http;

use App\Framework\Http\HttpMethod;
use App\Framework\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function test_path_strips_query_string(): void
    {
        $this->assertSame('/list/london', (new Request(HttpMethod::GET, '/list/london?page=2&q=a?b'))->path());
        $this->assertSame('/list/london', (new Request(HttpMethod::GET, '/list/london'))->path());
    }

    public function test_header_lookup_is_case_insensitive(): void
    {
        $request = new Request(HttpMethod::GET, '/', headers: ['accept' => 'text/html']);

        $this->assertSame('text/html', $request->header('Accept'));
        $this->assertNull($request->header('x-missing'));
    }

    public function test_wants_json(): void
    {
        $json = new Request(HttpMethod::GET, '/', headers: ['accept' => 'application/json, text/plain']);
        $html = new Request(HttpMethod::GET, '/', headers: ['accept' => 'text/html']);
        $none = new Request(HttpMethod::GET, '/');

        $this->assertTrue($json->wantsJson());
        $this->assertFalse($html->wantsJson());
        $this->assertFalse($none->wantsJson());
    }

    public function test_inputs_uses_query_for_get(): void
    {
        $request = new Request(HttpMethod::GET, '/', query: ['a' => '1'], parsedBody: ['b' => '2']);

        $this->assertSame(['a' => '1'], $request->inputs());
    }

    public function test_inputs_uses_body_for_methods_with_body(): void
    {
        foreach ([HttpMethod::POST, HttpMethod::PUT, HttpMethod::PATCH, HttpMethod::DELETE] as $method) {
            $request = new Request($method, '/', query: ['a' => '1'], parsedBody: ['b' => '2']);

            $this->assertSame(['b' => '2'], $request->inputs(), $method->value);
        }
    }

    public function test_form_token_returns_null_unless_non_empty_string(): void
    {
        $request = new Request(HttpMethod::POST, '/', parsedBody: [
            'valid'  => 'abc',
            'empty'  => '',
            'array'  => ['abc'],
        ]);

        $this->assertSame('abc', $request->formToken('valid'));
        $this->assertNull($request->formToken('empty'));
        $this->assertNull($request->formToken('array'));
        $this->assertNull($request->formToken('missing'));
    }

    public function test_ip(): void
    {
        $this->assertSame('1.2.3.4', (new Request(HttpMethod::GET, '/', serverParams: ['REMOTE_ADDR' => '1.2.3.4']))->ip());
        $this->assertNull((new Request(HttpMethod::GET, '/', serverParams: ['REMOTE_ADDR' => '']))->ip());
    }
}
