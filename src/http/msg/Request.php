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

use gabbro\http\msg\Request\ContentParser;
use gabbro\http\msg\Request\Verb;
use gabbro\io\Stream;
use gabbro\exception\NotFoundException;

/**
 * Defines a Request object for the http message specification.
 */
interface Request extends Message {

    /**
     * Get a response object configured for this request.
     * This method will always return the same instance of the response.
     *
     * This method allows you to directly change the status of the response.
     * Make sure not to override any existing and important status codes that may
     * already be set. 
     *
     * @param Response::STATUS_*|null $code    An optional response code.
     * @param string|null $reasonPhrase        An optional response phrase
     *
     * @return Response
     */
    function getResponse(int|null $code = null, string|null $reasonPhrase = null): Response;
    
    /**
     * Change the response object for this request.
     *
     * @param Response $response
     *
     * @return void
     */
    function setResponse(Response $response): void;

    /**
     * Add a parser for a given content type.
     *
     * The parser being added is used to parse the stream data when calling
     * {@see Request::getParsedData()}.
     *
     * @param ContentParser<mixed>
     *        |callable(Stream,string|null):mixed $parser      A parser to add.
     *
     * @param string|null $mimeType             The mime type that this parser can deal with.
     *                                          A `NULL` value indicates any type.
     *                                          If a parser exists for the specified mime type, then it will be used. 
     *                                          The NULL parser will only be used if no parser exists for this type.
     *
     * @param string $mimeTypes                 Additional mime types to support.
     *
     * @return void
     */
    function addParser(ContentParser|callable $parser, string|null $mimeType = null, string ...$mimeTypes): void;

    /**
     * Get the parsed data from the request stream.
     *
     * There is no proper way to enforce static types on this.
     * Data can be anything from form data to json encoding, XML and more.
     *
     * @note        This requires a parser being added that matches the content type
     *              of the stream. See {@see Request::setParser()}.
     *
     * @return mixed|null
     */
    function getParsedStream(): mixed;

    /**
     * Set whether or not the `Host` header should be updated to match {@see Uri}.
     * If this is enabled, then the host header takes priority. Otherwise
     * the host header will be based on the Uri, unless that does not exist. 
     *
     * @param bool $flag
     *
     * @return void
     */
    function setPreserveHost(bool $flag): void;
    
    /**
     * Check to see if one or more verbs is set.
     * This will return `true` if any of the verbs
     * are set in this request.
     *
     * @param int <1,max> $verb    One or more verbs.
     *
     * @see Verb
     * @return bool
     */
    function hasVerb(int $verb): bool;
    
    /**
     * Get the verb. 
     *
     * @return Verb
     */
    function getVerb(): Verb;
    
    /**
     * Set a new Verb on this request. 
     *
     * @param Verb|int<1,max> $verb
     *
     * @return void
     */
    function setVerb(Verb|int $verb): void;

    /**
     * Get the Uri object accociated with this request.
     *
     * @return Uri
     */
    function getUri(): Uri;

    /**
     * Set a new Uri object.
     *
     * @param Uri|string $uri      The new Uri object or string.
     *
     * @return void
     */
    function setUri(Uri|string $uri): void;

    /**
     * Get the request target.
     *
     * This is build from the Uri object in origin-form, 
     * unless it has been set manually.
     *
     * @return string
     */
    function getRequestTarget(): string;

    /**
     * Set a new request target.
     *
     * @note        This will stop the request target from being updated
     *              when changing the Uri object.
     *
     * @param string|null $requestTarget         The new request target.
     *
     * @return void
     */
    function setRequestTarget(string|null $requestTarget): void;
    
    /**
     * Check whether this is a secure connection.
     *
     * @return bool
     */
    function isSecure(): bool;

    /**
     * Check whether or not a file exists within this request.
     *
     * @param string $name      This is the name associated with {@see File::getName()}.
     *
     * @return bool
     */
    function hasFile(string $name): bool;

    /**
     * Get a specific file from this request.
     *
     * @note                        Multiple files with the same name may exist.
     *                              This will return the first one it finds.
     *
     * @param string $name          This is the name associated with {@see File::getName()}.
     *
     * @return File
     * @throws NotFoundException    If no file with this name exists. 
     */
    function getFile(string $name): File;

    /**
     * Get all the files in this request.
     *
     * @param string|null $name     Limit the list to files with a specific name.
     *
     * @return list<File>           This will return a list with all of the requested files.
     *                              If no files was found, an empty list is returned.
     */
    function getAllFiles(string|null $name = null): iterable;

    /**
     * Add a file to this request.
     *
     * @param File $file     The file to add.
     *
     * @return void
     */
    function addFile(File $file): void;

    /**
     * Remove all files or those based on a name.
     *
     * @note            In case of multiple files with the same name,
     *                  this method will remove all of them if name is declared.
     *
     * @param $name     The name of the file to remove or NULL for everything.
     *
     * @return void
     */
    function removeFiles(string|null $name = null): void;
}
