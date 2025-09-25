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
use gabbro\http\ServerRequest;
use gabbro\http\Verb;
use gabbro\io\RawStream;

final class ServerRequestTest extends TestCase {
    protected function setUp(): void {
        // Reset globals before each test
        $_SERVER = [];
        $_FILES  = [];
        $_POST   = [];
    }

    public function testServerRequestParsesGlobals(): void {
        // Fake server environment
        $_SERVER["REQUEST_METHOD"] = "POST";
        $_SERVER["HTTP_HOST"]      = "example.com";
        $_SERVER["SERVER_PORT"]    = "8080";
        $_SERVER["REQUEST_URI"]    = "/api/resource?id=42";
        $_SERVER["HTTPS"]          = "on";
        $_SERVER["HTTP_AUTHORIZATION"] = "Basic " . base64_encode("user:pass");
        $_SERVER["CONTENT_TYPE"]   = "application/json";

        // Fake php://input (our Stream should read from it)
        $json = json_encode(["hello" => "world"]);
        $stream = new RawStream();
        $stream->write($json);

        $req = new ServerRequest();
        $req->setStream($stream);

        // Method detection
        $this->assertTrue($req->hasVerb(Verb::POST));

        // Headers
        $this->assertTrue($req->hasHeader("Content-Type"));
        $this->assertSame("application/json", $req->getHeader("Content-Type"));
        
        $this->assertTrue($req->hasHeader("Host"));
        $this->assertSame("example.com", $req->getHeader("Host"));

        // URI
        $uri = $req->getUri();
        $this->assertSame("https", $uri->getScheme());
        $this->assertSame("user:pass@example.com:8080", $uri->getAuthority());
        $this->assertSame("/api/resource", $uri->getPath());
        $this->assertSame("id=42", $uri->getQuerystring());

        // Content parser (application/json)
        $parsed = $req->getParsedStream();
        $this->assertEquals((object)["hello" => "world"], $parsed);
    }
}
