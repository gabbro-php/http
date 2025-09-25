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

namespace gabbro\http\cookie;

use gabbro\exception\InvalidInputException;
use gabbro\exception\NotFoundException;
use gabbro\http\msg\Request;
use gabbro\http\msg\Response;
use gabbro\utils\Shift;

/**
 * Extension library that deals with Cookie headers. 
 *
 * This class acts as an extension to the HTTP library. 
 * It's task is to handle cookie specific headers in the Request and Response objects
 * and provide an easy and structured way of reading and writing cookies. 
 *
 * One key feature of this, unlike most Cookie handling extension, like PHP's own, 
 * is that this does not require a program to remember all of the attributes used to create
 * the cookie, just to be able to delete it again. To delete a Cookie, simply call {CookieRegistry::delete()} 
 * and pass the name of the cookie.
 */
class CookieRegistry {

    /**
     * Set `SameSite` to `Lax`.
     *
     * @var int = 0x01
     */
    public const ATTR_LAX = 0x01;

    /**
     * Set `SameSite` to `Strict`.
     *
     * @note        This will override `ATTR_LAX` if that is being set as well.
     *              Both cannot exist at the same time and `Strict` has higher priority.
     *
     * @var int = 0x03
     */
    public const ATTR_STRICT = 0x03;
    
    /**
     * Set `SameSite` to `None`.
     *
     * @note        This will override `ATTR_LAX` or `ATTR_STRICT` if they are being set as well.
     *              It will also automatically set `ATTR_SECURE` because this is a requirement 
     *              in most browsers when setting SameSite to None.
     *
     * @var int = 0x0B
     */
    public const ATTR_UNBOUND = 0x0F;

    /**
     * Only send the cookie via secure HTTPS connections.
     *
     * @var int = 0x08
     */
    public const ATTR_SECURE = 0x08;

    /**
     * Do not allow client scripts to access this cookie.
     *
     * @var int = 0x10
     */
    public const ATTR_NOSCRIPT = 0x10;

    /**
     * @ignore
     * 
     * Indicates that this cookie has been changed.
     */
    protected const CF_MODIFIED = 0x4000;
    
    /**
     * @ignore
     *
     * Indicates that this cookie did not come from the client.
     * (Implies CF_MODIFIED)
     */
    protected const CF_NEW = 0x6000; // 0x4000 | 0x2000

    /**
     * @ignore
     *
     * Indicates that this cookie has been deleted.
     * (Implies CF_MODIFIED)
     */
    protected const CF_DELETED = 0xC000; // 0x4000 | 0x8000
    
    /**
     * @ignore
     * 
     * Public attribute mask (ATTR_*).
     * Ensures only cookie attributes are kept.
     */
    protected const MASK_ATTR = 0x00FF;

    /**
     *
     * 
     * Internal flag mask (CF_*).
     * Ensures only internal flags are kept.
     */
    protected const MASK_CF = 0xF000;
    
    /** 
     * @ignore
     *
     * @var string
     */
    protected string $hash = "xxh64";
    
    /** 
     * @ignore
     *
     * @var string
     */
    protected string $prefix = "gabbro_";

    /** 
     * @ignore
     *
     * @var string|null
     */
    protected string|null $path = null;

    /** 
     * @ignore
     *
     * @var string|null
     */
    protected string|null $domain = null;

    /** 
     * @ignore
     *
     * @var array<string,array{
     *      "value": string|null,
     *      "expires": int<0,max>,
     *      "path": string|null,
     *      "domain": string|null,
     *      "flags": int
     * }>
     */
    protected array $cookies = [];
    
    /**
     * Create a new registry.
     *
     * @return void
     */
    public function __construct() {}
    
    /**
     * @ignore
     *
     * Encode a cookie value to make it safe to be used in a header.
     *
     * @param string $value     The value to encode.
     *
     * @return string           The encoded value.
     */
    protected function encodeValue(string $value): string {
        return rtrim(strtr(base64_encode($value), "+/", "-_"), "=");
    }
    
    /**
     * @ignore
     *
     * Decode a cookie value that was encoded using {@see CookieRegistry::encodeValue()}.
     *
     * @param string $value     The value to decode.
     *
     * @return string           The decoded value.
     */
    protected function decodeValue(string $value): string|null {
        if (($value = base64_decode(strtr($value, '-_', '+/'), true)) !== false) {
            // Not a complete validating, just a quick check to see if it may have been created by this class.
            if (!preg_match("/^\d+\|\d+\|/", $value)) {
                return null;
            }
        
            return $value;
        }
        
        return null;
    }
    
    /**
     * @ignore
     *
     * Encode the cookie name. 
     *
     * @param string $name      The name to encode.
     *
     * @return string           The encoded name.
     */
    protected function encodeName(string $name): string {
        return substr(hash($this->hash, "{$this->prefix}{$name}", false), -16);
    }
    
    /**
     * Set a domain which will be used on all cookies.
     *
     * @param string|null $host     Pass NULL to remove the domain.
     *
     * @return void
     */
    public function setGlobalHost(string|null $host): void {
        if ($host !== null) {
            $host = parse_url("scheme://{$host}", PHP_URL_HOST);
            
            if (!is_string($host)) {
                throw new InvalidInputException("Invalid cookie host");
            }
        }
        
        $this->domain = $host;
    }
    
    /**
     * Set a path that will be used on all cookies.
     *
     * This can still be overwritten by individual cookies
     * when using the {@see CookieRegistry::set()} method.
     *
     * @param string|null $path     Pass NULL to remove the path.
     *
     * @return void
     */
    public function setGlobalPath(string|null $path): void {
        if ($path !== null) {
            $path = parse_url($path, \PHP_URL_PATH);
            
            if (!is_string($path)) {
                throw new InvalidInputException("Invalid cookie path");
            }
        }
        
        $this->path = $path;
    }
    
    /**
     * Create or change value/attributes on a cookie.
     *
     * @param string $name                                  The name of the cookie.
     * @param int<0,max> $expires                           When the cookie expires or `0` for never. 
     *                                                      This is declared as time from now in seconds. 
     *
     * @param string|null $path                             Optional path for this cookie or NULL.
     * @param int-mask-of<CookieRegistry::ATTR_*> $flags    Additional attributes flags.
     *
     * @return void
     */
    public function set(string $name, string $value, int $expires = 0, string|null $path = null, int $flags = 0): void {
        $name = $this->encodeName($name);
    
        if (!isset($this->cookies[$name])) {
            $flags |= CookieRegistry::CF_NEW;
            
        } else {
            $oldFlags = $this->cookies[$name]["flags"];

            $internal = $oldFlags & CookieRegistry::MASK_CF;
            $internal &= ~CookieRegistry::CF_DELETED;
            $internal |= CookieRegistry::CF_MODIFIED;

            $flags |= $internal;
        }
        
        $this->cookies[$name] = [
            "value" => empty($value) ? null : $value,
            // @phpstan-ignore-next-line
            "expires" => $expires < 0 ? 0 : $expires,
            "path" => empty($path) ? null : $path,
            "domain" => empty($this->domain) ? null : $this->domain,
            "flags" => $flags
        ];
    }
    
    /**
     * Get the value from a specified cookie.
     *
     * @param string $name           The name of the cookie.
     *
     * @return string|null           Return NULL for empty value.
     * @throws NotFoundException     If the cookie could not be found.
     */
    public function get(string $name): string|null {
        $name = $this->encodeName($name);
        
        if (!isset($this->cookies[$name])
                || ($this->cookies[$name]["flags"] & CookieRegistry::CF_DELETED) === CookieRegistry::CF_DELETED) {
        
            throw new NotFoundException("The requested cookie does not exist");
        }
        
        return $this->cookies[$name]["value"];
    }
    
    /**
     * Check to see if a cookie exist.
     *
     * @param string $name      The cookie name to check.
     *
     * @return bool
     */
    public function isSet(string $name): bool {
        $name = $this->encodeName($name);
        
        return isset($this->cookies[$name])
                && ($this->cookies[$name]["flags"] & CookieRegistry::CF_DELETED) !== CookieRegistry::CF_DELETED;
    }
    
    /** 
     * Delete a cookie.
     *
     * @note        If this cookie was read from a client Request,
     *              it will not truly be deleted until this registry is
     *              written and sent back to the client. 
     *
     * @param string $name      The name of the cookie to delete.
     *
     * @return void
     */
    public function delete(string $name): void {
        $name = $this->encodeName($name);
        
        if (isset($this->cookies[$name])
                && ($this->cookies[$name]["flags"] & CookieRegistry::CF_NEW) === CookieRegistry::CF_NEW) {
            
            /*
             * This has not yet been sent to the client, 
             * so we can just remove it.
             */
            unset($this->cookies[$name]);
            
        } else if (isset($this->cookies[$name])) {
            /*
             * Don't do anything yet. 
             * Simply mark it as deleted, we need to update the client.
             */
            $this->cookies[$name]["flags"] |= CookieRegistry::CF_DELETED;
        }
    }
    
    /**
     * Parse cookie data from a request object.
     *
     * @param Request $request      The request that contains the cookie header.
     *
     * @return void
     */
    public function readFromRequest(Request $request): void {
        if ($request->hasHeader("Cookie")) {
            $parts = array_map("trim", explode(";", $request->getHeader("Cookie")));
            
            foreach ($parts as $part) {
                if (empty($part)) {
                    continue;
                }
                
                [$name, $data] = array_pad(explode("=", $part, 2), 2, "");
                
                if (($data = $this->decodeValue($data)) !== null) {
                    [$flags, $expires, $domain, $path, $value] = array_pad(explode("|", $data, 5), 5, "");
                    
                    $expires = intval($expires);
                    $flags = intval($flags);
                    
                    $this->cookies[$name] = [
                        "value" => empty($value) ? null : $value,
                        "expires" => $expires < 0 ? 0 : $expires,
                        "path" => empty($path) ? null : $path,
                        "domain" => empty($domain) ? null : $domain,
                        "flags" => $flags
                    ];
                }
            }
        }
    }
    
    /**
     * Write cookie data to the headers of a response object.
     *
     * @param Response $response        The response object to write headers to.
     *
     * @return void
     */
    public function writeToResponse(Response $response): void {
        foreach ($this->cookies as $name => $cookie) {
            if (!($cookie["flags"] & CookieRegistry::CF_MODIFIED)) {
                continue;
            }
            
            $domain = $cookie["domain"];
            $path = $cookie["path"];
            
            if (($cookie["flags"] & CookieRegistry::CF_DELETED) !== CookieRegistry::CF_DELETED) {
                $value = (string) ($cookie["flags"] & CookieRegistry::MASK_ATTR);
                $value .= "|" . (string) $cookie["expires"];
                $value .= "|" . ($cookie["domain"] ?? "");
                $value .= "|" . ($cookie["path"] ?? "");
                $value .= "|" . ($cookie["value"] ?? "");
                
                $value = $this->encodeValue($value);
                $expires = $cookie["expires"] > 0 ? $cookie["expires"] : 0;
                
            } else {
                $value = "deleted";
                $expires = (3600 * 24 * 365) * -1;
            }
            
            if ($domain !== null) {
                $value .= "; Domain={$domain}";
            }
            
            if ($path !== null) {
                $value .= "; Path={$path}";
            }
            
            if ($expires != 0) {
                $value .= sprintf("; Expires=%s; Max-Age=%s", gmdate("D, d M Y H:i:s T", time() + $expires), $expires);
            }
            
            if (($cookie["flags"] & CookieRegistry::ATTR_UNBOUND) === CookieRegistry::ATTR_UNBOUND) {
                $value .= "; SameSite=None";
            
            } else if (($cookie["flags"] & CookieRegistry::ATTR_STRICT) === CookieRegistry::ATTR_STRICT) {
                $value .= "; SameSite=Strict";
            
            } else if (($cookie["flags"] & CookieRegistry::ATTR_LAX) === CookieRegistry::ATTR_LAX) {
                $value .= "; SameSite=Lax";
            }
            
            if ($cookie["flags"] & CookieRegistry::ATTR_SECURE) {
                $value .= "; Secure";
            }
            
            if ($cookie["flags"] & CookieRegistry::ATTR_NOSCRIPT) {
                $value .= "; HttpOnly";
            }
            
            $response->addHeader("Set-Cookie", "{$name}={$value}");
        }
    }
}

