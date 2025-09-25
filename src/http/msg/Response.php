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

use gabbro\feature\Stringable;

/**
 * Defines a Response object for the http message specification.
 *
 * **Example:**
 * Download a file from the server
 *
 * ```php
 * $response = ...;
 * $response->setHeader("Content-Type", "application/octet-stream; charset=" . mime_content_type($file));
 * $response->setHeader("Content-Disposition", "attachment; filename={$filename}");
 * $response->setHeader("Content-Length", $filesize);
 * $response->setStream(new FileStream($file));
 *
 * // Send content to the client
 * $response->send();
 * ```
 */
interface Response extends Message {

    /* Status codes
     *
     * - 1xxx: Informational    - Request received, continuing process
     * - 2xx: Success           - The action was successfully received, understood, and accepted
     * - 3xx: Redirection       - Further action must be taken in order to complete the request
     * - 4xx: Client Error      - The request contains bad syntax or cannot be fulfilled
     * - 5xx: Server Error      - The server failed to fulfil an apparently valid request
     */

    /** @var int */
    const STATUS_CONTINUE = 100;

    /** @var int */
    const STATUS_SWITCHING_PROTOCOLS = 101;

    /** @var int */
    const STATUS_PROCESSING = 102;

    /** @var int */
    const STATUS_OK = 200;

    /** @var int */
    const STATUS_CREATED = 201;

    /** @var int */
    const STATUS_ACCEPTED = 202;

    /** @var int */
    const STATUS_NON_AUTHORITATIVE_INFORMATION = 203;

    /** @var int */
    const STATUS_NO_CONTENT = 204;

    /** @var int */
    const STATUS_RESET_CONTENT = 205;

    /** @var int */
    const STATUS_PARTIAL_CONTENT = 206;

    /** @var int */
    const STATUS_MULTI_STATUS = 207;

    /** @var int */
    const STATUS_ALREADY_REPORTED = 208;

    /** @var int */
    const STATUS_IM_USED = 226;

    /** @var int */
    const STATUS_MULTIPLE_CHOICES = 300;

    /** @var int */
    const STATUS_MOVED_PERMANENTLY = 301;

    /** @var int */
    const STATUS_FOUND = 302;

    /** @var int */
    const STATUS_SEE_OTHER = 303;

    /** @var int */
    const STATUS_NOT_MODIFIED = 304;

    /** @var int */
    const STATUS_USE_PROXY = 305;

    /** @var int */
    const STATUS_RESERVED = 306;

    /** @var int */
    const STATUS_TEMPORARY_REDIRECT = 307;

    /** @var int */
    const STATUS_PERMANENT_REDIRECT = 308;

    /** @var int */
    const STATUS_BAD_REQUEST = 400;

    /** @var int */
    const STATUS_UNAUTHORIZED = 401;

    /** @var int */
    const STATUS_PAYMENT_REQUIRED = 402;

    /** @var int */
    const STATUS_FORBIDDEN = 403;

    /** @var int */
    const STATUS_NOT_FOUND = 404;

    /** @var int */
    const STATUS_METHOD_NOT_ALLOWED = 405;

    /** @var int */
    const STATUS_NOT_ACCEPTABLE = 406;

    /** @var int */
    const STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;

    /** @var int */
    const STATUS_REQUEST_TIMEOUT = 408;

    /** @var int */
    const STATUS_CONFLICT = 409;

    /** @var int */
    const STATUS_GONE = 410;

    /** @var int */
    const STATUS_LENGTH_REQUIRED = 411;

    /** @var int */
    const STATUS_PRECONDITION_FAILED = 412;

    /** @var int */
    const STATUS_PAYLOAD_TOO_LARGE = 413;

    /** @var int */
    const STATUS_URI_TOO_LONG = 414;

    /** @var int */
    const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;

    /** @var int */
    const STATUS_RANGE_NOT_SATISFIABLE = 416;

    /** @var int */
    const STATUS_EXPECTATION_FAILED = 417;

    /** @var int */
    const STATUS_UNPROCESSABLE_ENTITY = 422;

    /** @var int */
    const STATUS_LOCKED = 423;

    /** @var int */
    const STATUS_FAILED_DEPENDENCY = 424;

    /** @var int */
    const STATUS_UPGRADE_REQUIRED = 426;

    /** @var int */
    const STATUS_PRECONDITION_REQUIRED = 428;

    /** @var int */
    const STATUS_TOO_MANY_REQUESTS = 429;

    /** @var int */
    const STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;

    /** @var int */
    const STATUS_UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    /** @var int */
    const STATUS_INTERNAL_SERVER_ERROR = 500;

    /** @var int */
    const STATUS_NOT_IMPLEMENTED = 501;

    /** @var int */
    const STATUS_BAD_GATEWAY = 502;

    /** @var int */
    const STATUS_SERVICE_UNAVAILABLE = 503;

    /** @var int */
    const STATUS_GATEWAY_TIMEOUT = 504;

    /** @var int */
    const STATUS_VERSION_NOT_SUPPORTED = 505;

    /** @var int */
    const STATUS_VARIANT_ALSO_NEGOTIATES = 506;

    /** @var int */
    const STATUS_INSUFFICIENT_STORAGE = 507;

    /** @var int */
    const STATUS_LOOP_DETECTED = 508;

    /** @var int */
    const STATUS_NOT_EXTENDED = 510;

    /** @var int */
    const STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;

    /* =======================================================
     * Below starts the code reason phrases                 */

    /** @var string */
    const REASON_100 = "Continue";

    /** @var string */
    const REASON_101 = "Switching Protocols";

    /** @var string */
    const REASON_102 = "Processing";

    /** @var string */
    const REASON_200 = "OK";

    /** @var string */
    const REASON_201 = "Created";

    /** @var string */
    const REASON_202 = "Accepted";

    /** @var string */
    const REASON_203 = "Non-authoritative Information";

    /** @var string */
    const REASON_204 = "No Content";

    /** @var string */
    const REASON_205 = "Reset Content";

    /** @var string */
    const REASON_206 = "Partial Content";

    /** @var string */
    const REASON_207 = "Multi-Status";

    /** @var string */
    const REASON_208 = "Already Reported";

    /** @var string */
    const REASON_226 = "IM Used";

    /** @var string */
    const REASON_300 = "Multiple Choices";

    /** @var string */
    const REASON_301 = "Moved Permanently";

    /** @var string */
    const REASON_302 = "Found";

    /** @var string */
    const REASON_303 = "See Other";

    /** @var string */
    const REASON_304 = "Not Modified";

    /** @var string */
    const REASON_305 = "Use Proxy";

    /** @var string */
    const REASON_306 = "Reserved";

    /** @var string */
    const REASON_307 = "Temporary Redirect";

    /** @var string */
    const REASON_308 = "Permanent Redirect";

    /** @var string */
    const REASON_400 = "Bad Request";

    /** @var string */
    const REASON_401 = "Unauthorized";

    /** @var string */
    const REASON_402 = "Payment Required";

    /** @var string */
    const REASON_403 = "Forbidden";

    /** @var string */
    const REASON_404 = "Not Found";

    /** @var string */
    const REASON_405 = "Method Not Allowed";

    /** @var string */
    const REASON_406 = "Not Acceptable";

    /** @var string */
    const REASON_407 = "Proxy Authentication Required";

    /** @var string */
    const REASON_408 = "Request Timeout";

    /** @var string */
    const REASON_409 = "Conflict";

    /** @var string */
    const REASON_410 = "Gone";

    /** @var string */
    const REASON_411 = "Length Required";

    /** @var string */
    const REASON_412 = "Precondition Failed";

    /** @var string */
    const REASON_413 = "Payload Too Large";

    /** @var string */
    const REASON_414 = "Request-URI Too Long";

    /** @var string */
    const REASON_415 = "Unsupported Media Type";

    /** @var string */
    const REASON_416 = "Range Not Satisfiable";

    /** @var string */
    const REASON_417 = "Expectation Failed";

    /** @var string */
    const REASON_422 = "Unprocessable Entity";

    /** @var string */
    const REASON_423 = "Locked";

    /** @var string */
    const REASON_424 = "Failed Dependency";

    /** @var string */
    const REASON_426 = "Upgrade Required";

    /** @var string */
    const REASON_428 = "Precondition Required";

    /** @var string */
    const REASON_429 = "Too Many Requests";

    /** @var string */
    const REASON_431 = "Request Header Fields Too Large";

    /** @var string */
    const REASON_451 = "Unavailable For Legal Reasons";

    /** @var string */
    const REASON_500 = "Internal Server Error";

    /** @var string */
    const REASON_501 = "Not Implemented";

    /** @var string */
    const REASON_502 = "Bad Gateway";

    /** @var string */
    const REASON_503 = "Service Unavailable";

    /** @var string */
    const REASON_504 = "Gateway Timeout";

    /** @var string */
    const REASON_505 = "HTTP Version Not Supported";

    /** @var string */
    const REASON_506 = "Variant Also Negotiates";

    /** @var string */
    const REASON_507 = "Insufficient Storage";

    /** @var string */
    const REASON_508 = "Loop Detected";

    /** @var string */
    const REASON_510 = "Not Extended";

    /** @var string */
    const REASON_511 = "Network Authentication Required";

    /**
     * Return the current status code.
     *
     * @return Response::STATUS_*
     */
    function getStatusCode(): int;

    /**
     * Return the current status reason phrase.
     *
     * @return string
     */
    function getStatusReason(): string;

    /**
     * Set a new status code and optional reason phrase.
     *
     * If reason is not passed, then the default phrase
     * for the specified code is used. 
     *
     * @param Response::STATUS_* $code          A response code.
     * @param string|null $reasonPhrase         An optional response phrase
     *
     * @return void
     */
    function setStatus(int $code, string|null $reasonPhrase = null): void;
    
    /**
     * Send message to stdout.
     *
     * This is similar to {@see Stringable::toString()} except that it
     * will directly output this to the client. 
     *
     *  - Headers will be sent rather than just printed.
     *  - The {@see Stream} is also sent directly rather than being written to memory.
     *
     * @return void
     */
    function send(): void;
}
