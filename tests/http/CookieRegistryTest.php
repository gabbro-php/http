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
use gabbro\exception\NotFoundException;
use gabbro\http\cookie\CookieRegistry;
use gabbro\http\Request;
use gabbro\http\Response;

final class CookieRegistryTest extends TestCase {

    public function testSetAndGetCookie(): void {
        $registry = new CookieRegistry();
        $registry->set("session", "abc123", 3600, "/", CookieRegistry::ATTR_LAX | CookieRegistry::ATTR_SECURE);

        $this->assertTrue($registry->isSet("session"));
        $this->assertSame("abc123", $registry->get("session"));
    }

    public function testDeleteNewCookieRemovesCompletely(): void {
        $registry = new CookieRegistry();
        $registry->set("temp", "value");

        $this->assertTrue($registry->isSet("temp"));
        $registry->delete("temp");
        $this->assertFalse($registry->isSet("temp"));
    }

    public function testDeleteExistingCookieMarksDeleted(): void {
        $registry = new CookieRegistry();
        $registry->set("user", "daniel");

        // simulate sending once
        $response = new Response();
        $registry->writeToResponse($response);

        $registry->delete("user");

        $this->assertFalse($registry->isSet("user"));
        $this->expectException(NotFoundException::class);
        $registry->get("user");
    }
    
    public function testReadFromRequestParsesCookieHeader(): void {
        /*
         * Write cookie to response header
         */
        $registry = new CookieRegistry();
        $registry->set("MyCookie", "Some Value");
        
        $response = new Response();
        $registry->writeToResponse($response);
        
        $header = $response->getHeader("Set-Cookie");
        $this->assertStringContainsString("=", $header);
        
        /*
         * Read cookie back through request header
         */
        $cookieLine = explode(";", $header)[0]; // "encodedName=encodedValue"
        $request = new Request();
        $request->addHeader("Cookie", $cookieLine);
        
        $registry = new CookieRegistry();
        $registry->readFromRequest($request);
        
        $this->assertTrue($registry->isSet("MyCookie"));
        $this->assertSame("Some Value", $registry->get("MyCookie"));
        
        /*
         * Delete the cookie and write changes to a response
         */
        $registry->delete("MyCookie");
        $response = new Response();
        $registry->writeToResponse($response);
        
        $header = $response->getHeader("Set-Cookie");
        $this->assertStringContainsString("=deleted", $header);
    }

    public function testSameSiteLaxIsApplied(): void {
        $registry = new CookieRegistry();
        $registry->set("laxCookie", "value", 3600, "/", CookieRegistry::ATTR_LAX);

        $response = new Response();
        $registry->writeToResponse($response);

        $header = $response->getHeader("Set-Cookie");
        $this->assertStringContainsString("SameSite=Lax", $header);
    }

    public function testSameSiteStrictIsApplied(): void {
        $registry = new CookieRegistry();
        $registry->set("strictCookie", "value", 3600, "/", CookieRegistry::ATTR_STRICT);

        $response = new Response();
        $registry->writeToResponse($response);

        $header = $response->getHeader("Set-Cookie");
        $this->assertStringContainsString("SameSite=Strict", $header);
    }

    public function testSameSiteUnboundIsApplied(): void {
        $registry = new CookieRegistry();
        $registry->set("unboundCookie", "value", 3600, "/", CookieRegistry::ATTR_UNBOUND);

        $response = new Response();
        $registry->writeToResponse($response);

        $header = $response->getHeader("Set-Cookie");
        $this->assertStringContainsString("SameSite=None", $header);
        $this->assertStringContainsString("Secure", $header, "Unbound should imply Secure");
    }
}
