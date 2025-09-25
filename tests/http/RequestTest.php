<?php declare(strict_types=1);
/*
 * This file is part of the Gabbro Project: https://github.com/Gabbro-PHP
 *
 * Copyright (c) 2025 Daniel Bergløv, License: MIT
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR
 * THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace gabbro\test\http;

use PHPUnit\Framework\TestCase;
use gabbro\http\Request;
use gabbro\http\Uri;
use gabbro\http\Verb;

final class RequestTest extends TestCase {
    public function testRequestLineFormatting(): void {
        $uri = new Uri("https://example.com/index.html?id=1");
        $verb = new Verb();
        $request = new Request($verb, $uri);

        $s = $request->toString();
        $this->assertStringContainsString("GET /index.html?id=1 HTTP/1.1", $s);
    }

    public function testHeadersAndBodyAreIncluded(): void {
        $uri = new Uri("https://example.com/foo");
        $verb = new Verb( Verb::POST );
        $request = new Request($verb, $uri);

        $request->addHeader("content-type", "application/json");
        $request->getStream()->write('{"a":1}');

        $s = $request->toString();
        $this->assertStringContainsString("Content-Type: application/json", $s);
        $this->assertStringContainsString('{"a":1}', $s);
    }
}
