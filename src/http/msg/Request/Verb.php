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

namespace gabbro\http\msg\Request;

use gabbro\feature\Stringable;

/**
 *
 */
interface Verb extends Stringable {

    /**
     * All known verbs including WebDAV extensions
     *
     * @var int = 0xFFFFF
     */
    const ANY = 0xFFFFF;
    
    /**
     * All core verbs
     *
     * @var int = 0x01FF
     */
    const ANY_CORE = 0x01FF;

    /**
     * @var int = 0x01
     */
    const GET = 0x01;

    /**
     * @var int = 0x02
     */
    const HEAD = 0x02;

    /**
     * @var int = 0x04
     */
    const POST = 0x04;

    /**
     * @var int = 0x08
     */
    const PUT = 0x08;

    /**
     * @var int = 0x10
     */
    const DELETE = 0x10;

    /**
     * @var int = 0x20
     */
    const CONNECT = 0x20;

    /**
     * @var int = 0x40
     */
    const OPTIONS = 0x40;

    /**
     * @var int = 0x80
     */
    const TRACE = 0x80;

    /**
     * @var int = 0x0100
     */
    const PATCH = 0x0100;

    /**
     * @var int = 0x0200
     */
    const PROPFIND = 0x0200;

    /**
     * @var int = 0x0400
     */
    const PROPPATCH = 0x0400;

    /**
     * @var int = 0x0800
     */
    const MKCOL = 0x0800;

    /**
     * @var int = 0x1000
     */
    const COPY = 0x1000;

    /**
     * @var int = 0x2000
     */
    const MOVE = 0x2000;

    /**
     * @var int = 0x4000
     */
    const LOCK = 0x4000;

    /**
     * @var int = 0x8000
     */
    const UNLOCK = 0x8000;

    /**
     * @var int = 0x10000
     */
    const SEARCH = 0x10000;

    /**
     * @var int = 0x20000
     */
    const BIND = 0x20000;

    /**
     * @var int = 0x40000
     */
    const REBIND = 0x40000;

    /**
     * @var int = 0x80000
     */
    const UNBIND = 0x80000;
    
    /**
     * Get the flags within this verb.
     *
     * @param int<1,max> $mask    Filter specific flags for the return.
     *
     * @return int<0,max>
     */
    function getFlags(int $mask = Verb::ANY): int;
    
    /**
     * Check to see if flags are available.
     *
     * Instead of returning the flags, this method will simply
     * check to see if flags are `0`. You can pass a mask
     * to only check specific flags. 
     *
     * @param int<1,max> $mask          Filter specific flags.
     *
     * @return bool
     */
    function hasFlags(int $mask = Verb::ANY): bool;
}

