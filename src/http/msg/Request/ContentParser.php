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

use gabbro\io\Stream;
use gabbro\exception\ParseException;

/**
 * Defines a parser that is used to parse the request body.
 *
 * @template T
 */
interface ContentParser {

    /**
     * Parse data from the body stream.
     *
     * @param Stream $stream            The stream to parse.
     * @param string $contentType       The content type of the stream content.
     *                                  This may include more than just the mime type e.g. charset etc.
     *                                  It may pass a NULL if the type is unknown to allow a parser to detect 
     *                                  the type itself.
     *
     * @return T|null                   The parsed data or NULL if not supported.
     *
     * @throws ParseException           On error an exception is thrown.
     */
    function parse(Stream $stream, string|null $contentType): mixed;
}
