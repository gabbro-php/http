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
use gabbro\http\Response;

final class ResponseTest extends TestCase {
    public function testStatusLineAndHeaders(): void {
        $response = new Response(200);
        $response->addHeader("content-type", "text/plain");
        $response->getStream()->write("Hello");

        $s = $response->toString();
        $this->assertStringContainsString("HTTP/1.1 200 OK", $s);
        $this->assertStringContainsString("Content-Type: text/plain", $s);
        $this->assertStringContainsString("Hello", $s);
    }

    public function testDefaultContentTypeIsInjected(): void {
        $response = new Response(200);
        $s = $response->toString();
        $this->assertStringContainsString("Content-Type: text/html; charset=utf-8", $s);
    }
}
