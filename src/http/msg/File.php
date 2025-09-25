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

use gabbro\io\Stream;
use gabbro\exception\IOException;

/**
 * Defines a file that can be attached to a request.
 * These files are typically used for uploaded files.
 */
interface File {

    /*
     * These defines the errors produced by PHP's upload files.
     */

    /**
     * No errors.
     */
    const STATUS_OK = UPLOAD_ERR_OK;
    
    /**
     * File too large (server limit).
     */
    const STATUS_ERR_INI_SIZE = UPLOAD_ERR_INI_SIZE;

    /**
     * File too large (form limit).
     */
    const STATUS_ERR_FORM_SIZE = UPLOAD_ERR_FORM_SIZE;
    
    /**
     * File only partially uploaded.
     */
    const STATUS_ERR_PARTIAL = UPLOAD_ERR_PARTIAL;
    
    /**
     * No file uploaded.
     */
    const STATUS_ERR_NO_FILE = UPLOAD_ERR_NO_FILE;
    
    /**
     * Missing temporary folder.
     */
    const STATUS_ERR_NO_TMP_DIR = UPLOAD_ERR_NO_TMP_DIR;
    
    /**
     * Failed to write file to disk.
     */
    const STATUS_ERR_CANT_WRITE = UPLOAD_ERR_CANT_WRITE;
    
    /**
     * A PHP extension stopped the upload.
     */
    const STATUS_ERR_EXTENSION = UPLOAD_ERR_EXTENSION;
    

    /**
     * Get an identifiable name.
     *
     * @note        On `POST` requests this will be the name of the HTML element
     *              of the uploaded file.
     *
     * @return string
     */
    function getName(): string;

    /**
     * Checks to see if there is an error code.
     *
     * @return bool     Returns `true` is no error code exist.
     */
    function isReady(): bool;

    /**
     * Get a stream access to this file.
     *
     * The initial file (before it's saved) should have only
     * `r` read access.
     *
     * Whenever it's not possible to create/re-create a stream
     * for some reason, a stream containing an empty temp resource
     * is returned.
     *
     * @return Stream
     * @throws IOException      If a stream could not be created.
     */
    function getStream(): Stream;

    /**
     * Check whether or not this file has been saved.
     *
     * If this returns `true` is not a indicator that the
     * file cannot be saved again to another location.
     * It simply means that this is no longer a temp file,
     * since it has been dealt with at least ones.
     * However, if the caller parsed a stream as target,
     * that stream may only have write access.
     *
     * Also note that this method could return `false`
     * even if the file has been saved. Someone could have
     * done so manually using `getStream()`. So the only thing that
     * can be concluded from this value, is whether or not the file
     * stream points at the original temp file or not.
     *
     * @return bool         Returns `true` if `save()` has been called successfully
     */
    function isSaved(): bool;

    /**
     * Get the byte length of the file or stream.
     *
     * In cases where the size could not be determined,
     * this method returns `-1`.
     *
     * @return int
     */
    function getLength(): int;

    /**
     * Get the error code.
     *
     * @return int     Should return `0` when there are no errors.
     */
    function getError(): int;

    /**
     * Get the file name provided by the request, if any.
     *
     * It's the job of the request to provide this information.
     * The implementation is allowed to do further steps to determine this,
     * but is not required to.
     *
     * @return string|null
     */
    function getClientFilename(): string|null;

    /**
     * Get the file media type provided by the request, if any.
     *
     * It's the job of the request to provide this information.
     * The implementation is allowed to do further steps to determine this,
     * but is not required to.
     *
     * @return string|null
     */
    function getClientMediaType(): string|null;

    /**
     * Save the content of this included file to a permanent location.
     *
     * This is the same as `moveTo()` on the PSR UploadedFile object.
     * This object however is not just intended for uploaded files.
     * It's simply a file or stream that has been included in a request,
     * how and by whom does not mater. And since this includes stream support,
     * the word `save` gives more meaning than `move` because you cannot
     * move a stream, but you can save it's content, just like you can save the
     * content from a file to a different location. What happens to the source
     * file or stream is a task for the request.
     *
     * Stream access will shift to the target stream on success. This allows anyone with access
     * to the file object to have access to the target content.
     *
     * @note                                The provided target stream may not have been given read access. 
     *                                      It only requires write access to save the content, so make sure to
     *                                      check the read mode before attempting to read from it.
     *
     * @param string|Stream $target         Target, file or stream to save the content to.
     * 
     * @return void
     * @throws IOException                  Throws on error.
     */
    function save(string|Stream $target): void;
}
