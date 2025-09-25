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

use Exception;
use gabbro\ErrorCatcher;
use gabbro\http\msg\File as IFile;
use gabbro\io\Stream;
use gabbro\io\FileStream;
use gabbro\io\RawStream;
use gabbro\exception\IOException;

/**
 * 
 */
class File implements IFile {

    /**
     * @ignore
     *
     * @var string
     */
    protected string $name;

    /**
     * @ignore
     *
     * @var int
     */
    protected int $error;

    /**
     * @ignore
     *
     * @var int
     */
    protected int $length;

    /**
     * @ignore
     *
     * @var string|null
     */
    protected string|null $clientName;

    /**
     * @ignore
     *
     * @var string|null
     */
    protected string|null $clientType;

    /**
     * @ignore
     *
     * @var string|Stream
     */
    protected string|Stream $file;

    /**
     * @ignore
     *
     * @var bool
     */
    protected bool $saved = false;

    /**
     * Create a new File object.
     *
     * @param string $name                  Name of this file. 
     * @param string|Stream $file           The file path or a Stream to the file.
     * @param int $length                   The file length.
     * @param int $error                    An error code if upload had an error.
     * @param string|null $clientName       The original name of this file.
     * @param string|null $clientType       The mimetype of the file.
     *
     * @return void
     */
    public function __construct(string $name, string|Stream $file, int $length = -1, int $error = 0, string|null $clientName = null, string|null $clientType = null) {
        $this->file = $file;
        
        if (!is_string($file) && !$file->isReadable()) {
            throw new IOException("Invalid Stream used in File. The Stream must be readable.");
            
        } else if (is_string($file) && !is_file($file)) {
            throw new IOException("The file does not exist.");
        }

        if ($length < 0) {
            if (is_string($file) && $error == 0) {
                $length = filesize($file);

                if ($length === false) {
                    $length = -1;
                }

            } else if (!is_string($file)) {
                $length = $file->getLength();
            }
        }

        $this->name = $name;
        $this->error = $error;
        $this->length = $length;
        $this->clientName = $clientName;
        $this->clientType = $clientType;
    }

    /**
     * @{inheritdoc}
     *
     * @override {@see IFile::getName()}
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @{inheritdoc}
     *
     * @override {@see IFile::getStream()}
     */
    public function getStream(): Stream {
        if ($this->error !== 0) {
            throw new IOException("Unable to open stream. An error exists.");
        
        } else if (is_string($this->file)) {
            $fh = fopen($this->file, "r");
            
            if ($fh === false) {
                throw new IOException("Unable to open stream.");
            }
            
            $this->file = new RawStream($fh);
        }

        return $this->file;
    }

    /**
     * @{inheritdoc}
     *
     * @override {@see IFile::isReady()}
     */
    public function isReady(): bool {
        return $this->error === 0;
    }

    /**
     * @{inheritdoc}
     *
     * @override {@see IFile::getLength()}
     */
    public function getLength(): int {
        return $this->length;
    }

    /**
     * @{inheritdoc}
     *
     * @override {@see IFile::getError()}
     */
    public function getError(): int {
        return $this->error;
    }

    /**
     * @{inheritdoc}
     *
     * @override {@see IFile::getClientFilename()}
     */
    public function getClientFilename(): string|null {
        return $this->clientName;
    }

    /**
     * @{inheritdoc}
     *
     * @override {@see IFile::getClientMediaType()}
     */
    public function getClientMediaType(): string|null {
        return $this->clientType;
    }

    /**
     * @{inheritdoc}
     *
     * @override {@see IFile::isSaved()}
     */
    public function isSaved(): bool {
        return $this->saved;
    }

    /**
     * @{inheritdoc}
     *
     * @override {@see IFile::save()}
     */
    public function save(string|Stream $target): void {
        if (!$this->isReady()) {
            throw new IOException("This file contains errors and cannot be moved");
            
        } else if ($target instanceof Stream && !$target->isWritable()) {
            throw new IOException("Target must be writable");
        
        } else if (is_string($target) && is_dir(dirname($target)) && !is_writable(dirname($target))) {
            throw new IOException("Target must be writable");
            
        } else if (is_string($target)) {
            $fh = fopen($target, "w+");
            
            if ($fh === false) {
                throw new IOException("Unable to open target stream.");
            }
            
            $target = new RawStream($fh);
        }
        
        $source = $this->getStream();

        if ($source->isMovable()) {
            $source->moveTo(0);
        }
        
        $this->length = $target->write($source);
        $source->close();
        $this->saved = true;
        $this->file = $target;
    }
}
