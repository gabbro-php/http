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

namespace gabbro\http\msg;

use gabbro\exception\InvalidInputException;
use gabbro\exception\NotFoundException;
use gabbro\io\RawStream;
use gabbro\io\Stream;

/**
 * Base message that implements all of the header logic.
 */
abstract class BaseMessage implements Message {

    /** 
     * @ignore
     * @var Stream|null
     */
    protected Stream|null $body = null;

    /** 
     * @ignore
     * @var array<string,list<string>>
     */
    protected array $headers = [];

    /** 
     * @ignore
     * @var array<string,mixed>
     */
    protected array $attributes = [];

    /** 
     * @ignore
     * @var Message::HTTP_*
     */
    protected int $protocol = Message::HTTP_V1;
    
    /**
     * @ignore
     *
     * PSR7 states that header keys must be Case-insensitive.
     * gabbro adopts this rule and so we need something
     * that can produce a proper case for the keys.
     *
     * @param string $key
     * @return string
     */
    protected function normaliseKey(string $key): string {
        return preg_replace_callback("/\b\w+\b/", function($match) { return ucfirst(strtolower($match[0])); }, str_replace(" ", "-", trim($key, " \t"))) ?? $key;
    }
    
    /**
     * @ignore
     *
     * Filter a header value
     *
     * Ensures CRLF header injection vectors are filtered.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * This method filters any values not allowed from the string, and is
     * lossy.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @see https://github.com/zendframework/zend-diactoros
     *
     * @param string $value
     * @return string
     */
    protected function filterValue(string $value): string {
        $value = trim(preg_replace("/[ \t]+/", " ", $value) ?? $value);
        $length = strlen($value);
        $filtered = '';

        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);

            // Detect continuation sequences
            if ($ascii === 13) {
                $lf = ord($value[$i + 1]);
                $ws = ord($value[$i + 2]);

                if ($lf === 10 &&
                        ($ws === 9 || $ws === 32)) {

                    $filtered .= $value[$i] . $value[$i + 1];
                    $i += 1;
                }

                continue;
            }

            // Non-visible, non-whitespace characters
            // 9 === horizontal tab
            // 32-126, 128-254 === visible
            // 127 === DEL
            // 255 === null byte
            if (($ascii < 32 && $ascii !== 9)
                || $ascii === 127
                || $ascii > 254
            ) {
                continue;
            }

            $filtered .= $value[$i];
        }

        return $filtered;
    }

    /**
     * {inheritdoc}
     *
     * @override {@see Message::getAttribute()}
     */
    public function getAttribute(string $name, mixed $default = null): mixed {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * {inheritdoc}
     *
     * @override {@see Message::hasAttribute()}
     */
    public function hasAttribute(string $name): bool {
        return isset($this->attributes[$name]);
    }

    /**
     * {inheritdoc}
     *
     * @override {@see Message::setAttribute()}
     */
    public function setAttribute(string $name, mixed $value): void {
        if ($value === null) {
            if (isset($this->attributes[$name])) {
                unset($this->attributes[$name]);
            }
        
        } else {
            $this->attributes[$name] = $value;
        }
    }

    /**
     * {inheritdoc}
     *
     * @override {@see Message::hasHeader()}
     */
    public function hasHeader(string $name): bool {
        $key = $this->normaliseKey($name);
        return !empty($this->headers[$key]);
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Message::inHeader()}
     */
    public function inHeader(string $name, string $substr): bool {
        $key = $this->normaliseKey($name);
        
        if (empty($this->headers[$key])) {
            return false;
        }
        
        foreach ($this->headers[$key] as $value) {
            if (stripos($value, $substr) !== false) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Message::getHeader()}
     */
    public function getHeader(string $name): string {
        $key = $this->normaliseKey($name);
        
        if (empty($this->headers[$key])) {
            throw new NotFoundException("The header $name could not be found");
        }
        
        return $this->headers[$key][0];
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Message::getAllHeaders()}
     */
    public function getAllHeaders(string|null $name = null): iterable {
        if ($name === null) {
            return $this->headers;
        }
        
        $key = $this->normaliseKey($name);
        
        if (empty($this->headers[$key])) {
            return [];
        }
        
        return $this->headers[$key];
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Message::addHeader()}
     */
    public function addHeader(string $name, string $value, bool $replace = true): void {
        $key = $this->normaliseKey($name);
        
        if (!isset($this->headers[$key]) || $replace) {
            $this->headers[$key] = [];
        }
        
        $this->headers[$key][] = $this->filterValue($value);
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Message::removeHeader()}
     */
    public function removeHeader(string $name): void {
        $key = $this->normaliseKey($name);
        
        if (isset($this->headers[$key])) {
            unset($this->headers[$key]);
        }
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Message::getProtocolVersion()}
     */
    public function getProtocolVersion(): int {
        return $this->protocol;
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Message::setProtocolVersion()}
     */
    public function setProtocolVersion(int $version): void {
        $this->protocol = $version;
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Message::getStream()}
     */
    public function getStream(): Stream {
        if ($this->body === null || $this->body->isClosed()) {
            $this->body = new RawStream();
        }

        return $this->body;
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Message::setStream()}
     */
    public function setStream(Stream $body): void {
        if ($body->isClosed()) {
            throw new InvalidInputException("The passed stream is closed");
        }

        $this->body = $body;
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Stringable::toString()}
     */
    public function toString(): string {
        $ret = [];
        $allHeaders = $this->getAllHeaders();

        foreach ($allHeaders as $name => $headers) {
            foreach ($headers as $header) {
                $ret[] = sprintf("%s: %s", $name, $header);
            }
        }

        return implode("\n", $ret);
    }
    
    /* =============================================
     * Used internally by PHP
     */
     
    /**
     * @ignore
     * @override {@see Stringable}
     */
    public function __toString() {
        return $this->toString();
    }
}

