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

use gabbro\exception\NotFoundException;
use gabbro\exception\IOException;
use gabbro\io\Stream;
use gabbro\io\RawStream;
use gabbro\io\IOStream;
use gabbro\http\msg\BaseMessage;
use gabbro\http\msg\Uri as IUri;
use gabbro\http\msg\File as IFile;
use gabbro\http\msg\Response as IResponse;
use gabbro\http\msg\Request as IRequest;
use gabbro\http\msg\Request\ContentParser;
use gabbro\http\msg\Request\Verb as IVerb;

/**
 * 
 */
class Request extends BaseMessage implements IRequest {

    /**
     * @ignore
     *
     * @var mixed|null
     */
    protected mixed $parsedBody = null;

    /**
     * @ignore
     *
     * @var list<array{
     *      "parser": ContentParser<mixed>|callable,
     *      "mimeType": array<string,bool>
     * }>
     */
    protected array $parsers = [];

    /**
     * @ignore
     *
     * @var list<IFile>
     */
    protected array $files = [];

    /**
     * @ignore
     *
     * @var string|null
     */
    private string|null $requestTarget = null;

    /**
     * @ignore
     *
     * @var IUri|null
     */
    private IUri|null $uri = null;

    /**
     * @ignore
     *
     * @var IVerb
     */
    private IVerb $verb;

    /**
     * @ignore
     *
     * @var bool
     */
    private bool $preserveHost = false;
    
    /**
     * @ignore
     *
     * @var IResponse|null
     */
    private IResponse|null $response = null;

    /**
     * Create a new Request.
     *
     * @param $verb     Request verbs.
     *                  See {@see IVerb}.
     *
     * @param $uri      The request URI.
     *
     * @return void
     */
    public function __construct(IVerb|null $verb = null, IUri|null $uri = null) {
        $this->setVerb($verb === null ? new Verb() : $verb);
        
        if ($uri !== null) {
            $this->setUri($uri);
        }
    }
    
    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::addParser()}
     */
    public function getResponse(): IResponse {
        if ($this->response === null) {
            $this->response = new Response();
            $this->response->setProtocolVersion(
                $this->getProtocolVersion()
            );
        }
    
        return $this->response;
    }
    
    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::addParser()}
     */
    public function setResponse(IResponse $response): void {
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::addParser()}
     */
    public function addParser(ContentParser|callable $parser, string|null $mimeType = null, string ...$mimeTypes): void {
        $mimeType = $mimeType === null ? "*" : strtolower($mimeType);
        $types = [$mimeType => true];
        
        foreach ($mimeTypes as $type) {
            $type = strtolower($type);
            $types[$type] = true;
        }
        
        $this->parsers[] = [
            "parser" => $parser,
            "mimeType" => $types
        ];
    }
    
    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::setParser()}
     */
    public function getParsedStream(): mixed {
        if ($this->parsedBody !== null) {
            return $this->parsedBody;
        }
    
        $mimeType = null;
        $contentType = null;
        
        if ($this->hasHeader("content-type")) {
            $contentType = $this->getHeader("content-type");
            
            if (($pos = strpos($contentType, ";")) !== false) {
                $mimeType = strtolower(substr($contentType, $pos));
                
            } else {
                $mimeType = strtolower($contentType);
            }
        }
        
        $runs = [$mimeType];
        if ($mimeType !== null) {
            $runs[] = null;
        }
        
        foreach ($runs as $mimeType) {
            foreach ($this->parsers as $p) {
                if (($mimeType !== null && isset($p["mimeType"][$mimeType])) 
                        || ($mimeType === null && isset($p["mimeType"]["*"]))) {
                
                    $parser = $p["parser"];
                    $stream = $this->getStream();
                    
                    if ($parser instanceof ContentParser) {
                        $content = $parser->parse($stream, $contentType);
                        
                    } else {
                        $content = $parser($stream, $contentType);
                    }
                    
                    if ($content !== null) {
                        return $this->parsedBody ??= $content;
                    }
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see BaseMessage::hasHeader()}
     */
    public function hasHeader(string $name): bool {
        if (parent::hasHeader($name)) {
            return true;
            
        } else if (strcasecmp("host", $name) == 0) {
            return $this->getUri()->getHost() !== null;
        }
        
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see BaseMessage::inHeader()}
     */
    public function inHeader(string $name, string $substr): bool {
        if (strcasecmp("host", $name) == 0
                && (!$this->preserveHost || !$this->hasHeader("Host"))) {
        
            $host = $this->getUri()->getHost();
            
            if ($host !== null) {
                return stripos($host, $substr) !== false;
            }
        }

        return parent::inHeader($name, $substr);
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see BaseMessage::getHeader()}
     */
    public function getHeader(string $name): string {
        if (strcasecmp("host", $name) == 0
                && (!$this->preserveHost || !$this->hasHeader("Host"))) {
                
            $host = $this->getUri()->getHost();
            
            if ($host !== null) {
                return $host;
            }
        }

        // Let this deal with missing header
        return parent::getHeader($name);
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see BaseMessage::getAllHeaders()}
     */
    public function getAllHeaders(string|null $name = null): iterable {
        if (($name === null || strcasecmp("host", $name) == 0)
                && (!$this->preserveHost || !$this->hasHeader("Host"))) {
                
            $host = $this->getUri()->getHost();
            
            if ($host !== null) {
                $list = [$host];
            
                if ($name !== null) {
                    return $list;
                }
                
                $map = ["Host" => $list];
                $headers = parent::getAllHeaders();
                
                foreach ($headers as $name => $headerList) {
                    if ($name != "Host") {
                        $map[$name] = $headerList;
                    }
                }
                
                return $map;
            }
        }
        
        return parent::getAllHeaders($name);
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::setPreserveHost()}
     */
    public function setPreserveHost(bool $flag): void {
        $this->preserveHost = $flag;
    }
    
    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::getVerb()}
     */
    function hasVerb(int $verb): bool {
        return $this->verb->hasFlags($verb);
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::getVerb()}
     */
    function getVerb(): IVerb {
        return $this->verb;
    }
    
    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::setVerb()}
     */
    function setVerb(IVerb $verb): void {
        $this->verb = $verb;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::getUri()}
     */
    public function getUri(): IUri {
        if ($this->uri === null) {
            $this->uri = new Uri();
        }

        return $this->uri;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::setUri()}
     */
    public function setUri(IUri $uri): void {
        $this->uri = $uri;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::getRequestTarget()}
     */
    public function getRequestTarget(): string {
        if ($this->requestTarget === null) {
            /*
             * Do not store this value in 'mRequestTarget'.
             * Unless 'setRequestTarget' has been used, we should always get this
             * from the Uri object, it may change.
             */

            $uri = $this->getUri();
            $path = $uri->getPath();
            $query = $uri->getQuerystring();
            $target = $path . (!empty($query) ? "?" : "") . $query;

            if (empty($target)) {
                return "/";
            }

            return $target;
        }

        return $this->requestTarget;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::getRequestTarget()}
     */
    public function setRequestTarget(string|null $requestTarget): void {
        if (!empty($requestTarget)) {
            $this->requestTarget = rawurlencode(rawurldecode(trim($requestTarget)));

        } else {
            $this->requestTarget = null;
        }
    }
    
    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::isSecure()}
     */
    public function isSecure(): bool {
        $version = $this->getProtocolVersion();
        
        if ($version == 3) {
            return true; // QUIC always uses TLS
        }

        // For 1.1 and 2, check scheme
        $scheme = strtolower(
            $this->getUri()->getScheme() ?? ""
        );
        
        return $scheme == "https";
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::hasFile()}
     */
    function hasFile(string $name): bool {
        try {
            $this->getFile($name);
            return true;
        
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::getFile()}
     */
    function getFile(string $name): IFile {
        foreach ($this->files as $file) {
            if ($file->getName() == $name) {
                return $file;
            }
        }
        
        throw new NotFoundException("The file $name could not be found");
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::getAllFiles()}
     */
    function getAllFiles(string|null $name = null): iterable {
        if ($name === null) {
            return $this->files;
        }
        
        $list = [];
        
        foreach ($this->files as $file) {
            if ($file->getName() == $name) {
                $list[] = $file;
            }
        }
        
        return $list;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::addFile()}
     */
    function addFile(IFile $file): void {
        $this->files[] = $file;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::removeFiles()}
     */
    function removeFiles(string|null $name = null): void {
        $list = [];
        
        if ($name !== null) {
            foreach ($this->files as $file) {
                if ($file->getName() != $name) {
                    $list[] = $file;
                }
            }
        }
        
        $this->files = $list;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see BaseMessage::getStream()}
     */
    public function getStream(): Stream {
        /* We copy input to a temp stream so that other instances may be created
         * with the original content intact.
         */
        if ($this->body === null) {
            $input = IOStream::getInstance(IOStream::STDIN);
            $input->moveTo(0);
        
            $this->body = parent::getStream();
            $this->body->write($input);
            $this->body->moveTo(0);
        }

        return $this->body;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IRequest::getStream()}
     */
    public function setStream(Stream $body): void {
        if (!$body->isReadable()) {
            throw new IOException("A request body must be readable");
        }

        parent::setStream($body);
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see BaseMessage::toString()}
     */
    public function toString(): string {
        $version = $this->getProtocolVersion();

        switch ($version) {
            case 1:
                // HTTP/1.1 text format
                return sprintf(
                    "%s %s HTTP/1.1\r\n%s\r\n\r\n%s",
                    $this->getVerb()->toString(),
                    $this->getRequestTarget(),
                    parent::toString(),  
                    $this->getStream()->toString()
                );

            default:
                // HTTP/2 and HTTP/3 pseudo-headers
                $authority = $this->hasHeader("host") ? $this->getHeader("host") : "unknown";
                $scheme    = $this->isSecure() ? "https" : "http";

                $lines = [];
                $lines[] = ":method: "   . $this->getVerb()->toString();
                $lines[] = ":path: "     . $this->getRequestTarget();
                $lines[] = ":scheme: "   . $scheme;
                $lines[] = ":authority: ". $authority;

                // Normal headers still exist logically
                $lines[] = parent::toString();

                return implode("\r\n", $lines) . "\r\n\r\n" . $this->getStream()->toString();
        }
    }
}

