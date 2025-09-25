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

use gabbro\exception\NotFoundException;
use gabbro\feature\Stringable;
use gabbro\feature\Enumerable;
use gabbro\io\Stream;

/**
 * Defines a message object for the http message specification
 */
interface Message extends Stringable {

    /** 
     * HTTP/1.1
     */
    public const HTTP_V1 = 1;
    
    /** 
     * HTTP/2
     */
    public const HTTP_V2 = 2;
    
    /** 
     * HTTP/3
     */
    public const HTTP_V3 = 3;

    /**
     * Retrieve a single attribute
     *
     * @template T
     * @param $name            Name of the attribute.
     * @param T $default       A default value for when the attribute does not exist.
     *
     * @return ($default is null ? mixed|null : T)
     */
    function getAttribute(string $name, mixed $default = null): mixed;

    /**
     * Check to see if an attribute exists.
     *
     * @param string $name     Name of the attribute.
     */
    function hasAttribute(string $name): bool;

    /**
     * Add/Change an attribute value
     *
     * @param string $name          Name of the attribute.
     * @param mixed|null $value     The value to set on the attribute or NULL to remove.
     *
     * @return void
     */
    function setAttribute(string $name, mixed $value): void;

    /**
     * Check to see if there is a header with this name.
     *
     * @note    Names are Case-insensitive.
     *
     * @param string $name      Name of the header.
     *
     * @return bool
     */
    function hasHeader(string $name): bool;
    
    /**
     * Check to see if a substring exists in a header.
     *
     * This will search each header with the specified name, 
     * to see if a certain substring exists.
     *
     * @param string $name      The name of the header to search.
     * @param string $substr    The substring to search for.
     *
     * @return bool
     */
    function inHeader(string $name, string $substr): bool;

    /**
     * Get header with a specified name.
     * If multiple headers exist, the first one found will be returned.
     *
     * @note    Names are Case-insensitive.
     *
     * @param string $name          The name of the header.
     *
     * @return string
     * @throws NotFoundException    If no header with this name was found.
     */
    function getHeader(string $name): string;
    
    /**
     * Get all headers from this message.
     *
     * The iterable key is the normalized header name,
     * e.g. content-type => Content-Type.
     *
     * @note    Names are Case-insensitive.
     *
     * @param string|null $name             Only return headers with the specified name.
     *
     * @return ($name is null ? iterable<string,list<string>> : list<string>)      If no headers was found, an empty iterable is returned.
     */
    function getAllHeaders(string|null $name = null): iterable;

    /**
     * Add a header.
     * 
     * There is no magic here. HTTP headers are messy and inconsistent. 
     * This method will not try to normalize them in any way. Headers
     * are added just as they are passed. 
     *
     * Each new header is added to a list of headers. 
     *
     * @param string $name          Name of the header.
     * @param string $value         Value for the new header.
     * @param bool $replace         Whether or not to replace any existing headers.
     *
     * @return void
     */
    function addHeader(string $name, string $value, bool $replace = true): void;

    /**
     * Remove all headers with the specified name.
     *
     * @param string $name          Name of the header.
     *
     * @return void
     */
    function removeHeader(string $name): void;

    /**
     * Get the protocol version.
     *
     * @return Message::HTTP_*
     */
    function getProtocolVersion(): int;

    /**
     * Set a different protocol version.
     *
     * @param Message::HTTP_* $version       The new protocol version.
     *
     * @return void
     */
    function setProtocolVersion(int $version): void;

    /**
     * Get the current body stream.
     *
     * @return Stream
     */
    function getStream(): Stream;

    /**
     * Set a new body stream object.
     *
     * @param Stream $body      The new stream to use
     *
     * @return void
     */
    function setStream(Stream $body): void;
}

