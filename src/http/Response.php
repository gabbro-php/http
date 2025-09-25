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

namespace gabbro\http;

use gabbro\http\msg\Response as IResponse;
use gabbro\http\msg\BaseMessage;
use gabbro\exception\IOException;
use gabbro\io\Stream;
use gabbro\io\IOStream;

/**
 * 
 */
class Response extends BaseMessage implements IResponse {

    /**
     * @ignore
     *
     * @var IResponse::STATUS_*
     */
    protected int $status;

    /**
     * @ignore
     *
     * @var string|null
     */
    protected string|null $reason = null;

    /**
     * Create a new Response.
     * 
     * @param IResponse::STATUS_* $code     The response code.
     * @return void
     */
    public function __construct(int $code = Response::STATUS_OK) {
        $this->setStatus($code);
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IResponse::getStatusCode()}
     */
    public function getStatusCode(): int {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IResponse::getStatusReason()}
     */
    public function getStatusReason(): string {
        if ($this->reason === null) {
            /*
             * PHPStan looks at the actual defined properties, which makes
             * the match check invalid, since it can never be lower than 100 or grater
             * than the largest defined 500. But this is just a static check. We still need to
             * validate actual running code, so tell PHPStan to treat this as a general int value.
             */
            /** @var int */
            $code = $this->getStatusCode();
            $const = IResponse::class . "::REASON_{$code}";

            if (!defined($const)) {
                return match (true) {
                    $code >= 100 && $code < 200 => "Informational",
                    $code >= 200 && $code < 300 => "Success",
                    $code >= 300 && $code < 400 => "Redirection",
                    $code >= 400 && $code < 500 => "Client Error",
                    $code >= 500 && $code < 600 => "Server Error",
                    default                     => "Unknown",
                };
            }
            
            /** @var string */
            $reason = constant($const);

            return $reason;
        }

        return $this->reason;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IResponse::setStatus()}
     */
    public function setStatus(int $code, string|null $reasonPhrase = null): void {
        $this->status = $code;

        if (!empty($reasonPhrase)) {
            $this->reason = $this->filterValue($reasonPhrase);

        } else {
            $this->reason = null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IResponse::setStream()}
     */
    public function setStream(Stream $body): void {
        if (!$body->isWritable()) {
            throw new IOException("A response body must be writable");
        }

        parent::setStream($body);
    }
    
    /**
     * {@inheritdoc}
     *
     * @override {@see IResponse::send()}
     */
    public function send(): void {
        // Remove any existing headers
        header_remove();
        http_response_code( $this->getStatusCode() );
    
        $body = $this->getStream();
        $headers = explode("\n", parent::toString());
        
        if (!$this->hasHeader("content-type")) {
            header("Content-Type: text/html; charset=utf-8");
        }
        
        foreach ($headers as $header) {
            header($header);
        }

        // Ensure that the body stream is at the beginning
        if ($body->isMovable()) {
            $body->moveTo(0);
        }
        
        $output = IOStream::getInstance(IOStream::STDOUT);
        $output->write($body);
    }
    
    /**
     * {@inheritdoc}
     *
     * @override {@see BaseMessage::toString()}
     */
    public function toString(): string {
        $version = $this->getProtocolVersion();
        $headers = parent::toString();

        // Ensure a Content-Type header is always present
        if (!$this->hasHeader("content-type")) {
            if (!empty($headers)) {
                $headers = "Content-Type: text/html; charset=utf-8\r\n" . $headers;
            } else {
                $headers = "Content-Type: text/html; charset=utf-8";
            }
        }

        switch ($version) {
            case 1:
                // HTTP/1.1 style
                return sprintf(
                    "HTTP/1.1 %s %s\r\n%s\r\n\r\n%s",
                    $this->getStatusCode(),
                    $this->getStatusReason(),
                    $headers,
                    $this->getStream()->toString()
                );

            default:
                // HTTP/2 & HTTP/3 pseudo-header style
                $lines = [];
                $lines[] = ":status: " . $this->getStatusCode();
                $lines[] = $headers;

                return implode("\r\n", $lines) . "\r\n\r\n" . $this->getStream()->toString();
        }
    }
}

