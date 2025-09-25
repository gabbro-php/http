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
use gabbro\http\msg\Request\Verb as IVerb;
use ReflectionClass;

/**
 *
 */
class Verb implements IVerb {

    /**
     * Get a new Verb from a string.
     * 
     * @param string $method        You can pass `Method1|Method2` to get multiple verbs.
     *
     * @return static
     */
    public static function fromString(string $method): static {
        $methods = array_map("strtoupper", explode("|", $method));
        $flags = 0;
        
        foreach ($methods as $method) {
            $const = IVerb::class . "::" . $method;
            
            if (!defined($const)) {
                throw new NotFoundException("Constant for $method could not be found");
            }
            
            /** @var int<1,max> */
            $value = constant($const);
            $flags |= $value;
        }
        
        if ($flags < 1) {
            $flags = IVerb::GET;
        }
        
        return new static($flags);
    }

    /**
     *
     */
    public final function __construct(
        /**
         * @ignore
         * @var int<1,max>
         */
        protected int $flags = IVerb::GET
    ) {}

    /**
     * {inheritdoc}
     *
     * @override {@see IVerb::getFlags}
     */
    public function getFlags(int $mask = IVerb::ANY): int {
        /*
         * PHPStan should know that doing a bitwise-and on two int within same range, 
         * can never go outside that range. Sadly this is not the case. 
         * So we must tell it once again what type this is.
         */
        /** @var int<0,max> */
        $flags = $this->flags & $mask;
    
        return $flags;
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see IVerb::hasFlags}
     */
    public function hasFlags(int $mask = IVerb::ANY): bool {
        return ($this->flags & $mask) > 0;
    }
    
    /**
     * {inheritdoc}
     *
     * @override {@see Stringable::toString()}
     */
    public function toString(): string {
        $ref = new ReflectionClass($this);
        $verbs = [];

        foreach ($ref->getConstants() as $name => $bit) {
            // Only include constants that are single-bit flags
            if (is_int($bit) && $bit > 0 && ($bit & ($bit - 1)) === 0) {
                if ($this->flags & $bit) {
                    $verbs[] = $name;
                }
            }
        }

        return implode("|", $verbs);
    }
    
    /* =============================================
     * Used internally by PHP
     */
     
    /**
     * @ignore
     * @override {@see Stringable}
     */
    public function __toString() {
        return $this->toString();
    }
}

