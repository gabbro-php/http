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

use gabbro\utils\Shift;
use gabbro\io\Stream;
use gabbro\http\msg\Request\ContentParser;

/**
 * An auto populating server request.
 * 
 * This implementation will be initiated and populated with data from
 * PHP's superglobals. This includes URI, Host, Uploaded files and more.
 */
class ServerRequest extends Request {

    /**
     * @return void
     */
    public function __construct() {
        parent::__construct();
        
        /* ------------------------------------------------
         * Get all headers
         */
        if (function_exists("getallheaders")) {
            foreach (getallheaders() as $key => $value) {
                $this->addHeader($key, $value);
            }
        } else {
            $keys = ["CONTENT_TYPE", "CONTENT_LENGTH"];
        
            /** @var string $value */
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, "HTTP_")) {
                    $this->addHeader(
                        substr(str_replace("_", "-", $key), 5), 
                        $value
                    );
                    
                } else if (in_array($key, $keys, true)) {
                    $this->addHeader(
                        str_replace("_", "-", $key), 
                        $value
                    );
                }
            }
        }
        
        /* ------------------------------------------------
         * Get the request Method
         */
        if ($this->hasHeader("X-HTTP-Method-Override")) {
            $this->setVerb(Verb::fromString(
                $this->getHeader("X-HTTP-Method-Override")
            ));
            
        } else if ($this->hasHeader("X-Method-Override")) {
            $this->setVerb(Verb::fromString(
                $this->getHeader("X-Method-Override")
            ));

        } else {
            /** @var string */
            $method = $_SERVER["REQUEST_METHOD"] ?: "GET";
        
            $this->setVerb(Verb::fromString(
                $method
            ));
        }
        
        /* ------------------------------------------------
         * Get URI information
         */
        /** @var string */
        $uriAuth = $_SERVER["HTTP_HOST"] 
                ?? $_SERVER["SERVER_NAME"] 
                ?? $_SERVER["SERVER_ADDR"] 
                ?? "localhost";
        
        $uriScheme = Shift::toBoolean($_SERVER["HTTPS"] ?? false) ? "https" : "http";
        /** @var string */
        $uriPath = $_SERVER["REQUEST_URI"] ?? "";
        $uriQuery = null;
        
        if (!empty($_SERVER["SERVER_PORT"])) {
            $uriAuth .= ":";
            $uriAuth .= Shift::toString($_SERVER["SERVER_PORT"]);
        }
        
        if (($pos = strpos($uriPath, "?")) !== false) {
            $uriQuery = substr($uriPath, $pos + 1);
            $uriPath = substr($uriPath, 0, $pos);
        }
        
        if ($this->hasHeader("Authorization")) {
            $userinfo = $this->getHeader("Authorization");

            if (strtolower(substr($userinfo, 0, 5)) == "basic") {
                $uriAuth = base64_decode(substr($userinfo, 6)) . "@{$uriAuth}";
            }
        }
        
        $uri = new Uri();
        $uri->setAuthority($uriAuth);
        $uri->setScheme($uriScheme);
        $uri->setPath($uriPath);
        
        if (!empty($uriQuery)) {
            $uri->setQuerystring($uriQuery);
        }
        
        $this->setUri($uri);
        
        /* ------------------------------------------------
         * Get uploaded files
         */
        if (!empty($_FILES)) {
            /**
             * @var array<
             *      string,
             *      array{
             *          "name":     list<string>,
             *          "type":     list<string>,
             *          "tmp_name": list<string>,
             *          "error":    list<int>,
             *          "size":     list<int>
             *      }
             *  > | array<
             *          string,
             *          array{
             *              "name":     string,
             *              "type":     string,
             *              "tmp_name": string,
             *              "error":    int,
             *              "size":     int
             *          }
             * > $_FILES
             */
            foreach ($_FILES as $fieldName => $fileInfo) {
                if (is_array($fileInfo["tmp_name"])) {
                    foreach ($fileInfo["tmp_name"] as $pos => $ignore) {
                        $this->addFile(new File(
                            $fieldName,
                            $fileInfo["tmp_name"][$pos],
                            $fileInfo["size"][$pos],
                            $fileInfo["error"][$pos],
                            basename($fileInfo["name"][$pos]),
                            $fileInfo["type"][$pos]
                        ));
                    }

                } else {
                    $this->addFile(new File(
                        $fieldName,
                        $fileInfo["tmp_name"],
                        $fileInfo["size"],
                        $fileInfo["error"],
                        basename($fileInfo["name"]),
                        $fileInfo["type"]
                    ));
                }
            }
        }
        
        /* ------------------------------------------------
         * Create a parser for the body content
         */
        if ($this->hasHeader("content-type")) {
            $parser = null;
            $mimeType = $this->getHeader("content-type");
            
            if (($pos = strpos($mimeType, ";")) !== false) {
                $mimeType = substr($mimeType, 0, $pos);
            }
            
            if ($this->hasVerb(Verb::POST)
                    && in_array(strtolower($mimeType), ["multipart/form-data", "application/x-www-form-urlencoded"])) {
            
                $parser = new class() implements ContentParser {
                    public function parse(Stream $stream, string|null $contentType): mixed {
                        return $_POST;
                    }
                };
            
            } else if (strtolower($mimeType) == "application/json") {
                $parser = new class() implements ContentParser {
                    public function parse(Stream $stream, string|null $contentType): mixed {
                        if ($stream->isMovable()) {
                            $stream->moveTo(0);
                        }
                    
                        return json_decode($stream->toString(), false);
                    }
                };
            }
            
            if ($parser !== null) {
                $this->addParser($parser, $mimeType);
            }
        }
    }
}

