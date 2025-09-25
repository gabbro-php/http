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

use gabbro\http\msg\Uri as IUri;
use gabbro\exception\InvalidInputException;

/**
 * 
 */
class Uri implements IUri {

    /** 
     * @ignore 
     *
     * @var string|null
     */
    protected string|null $scheme = null;

    /** 
     * @ignore 
     *
     * @var string|null
     */
    protected string|null $user = null;

    /** 
     * @ignore 
     *
     * @var string|null
     */
    protected string|null $passwd = null;

    /** 
     * @ignore 
     *
     * @var string|null
     */
    protected string|null $host = null;

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
    protected string|null $fragment = null;

    /** 
     * @ignore 
     *
     * @var array<string,string>
     */
    protected array $query;

    /** 
     * @ignore 
     *
     * @var int<0,65535>
     */
    protected int $port = 0;

    /**
     * Create a new Uri object.
     *
     * **Example**
     * ```php
     * new Uri("https://user:passwd@domain.com/path");
     * ```
     *
     * @param string $url      Optional URI string.
     *
     * @return void
     */
    public function __construct(string|null $url = null) {
        if ($url != null) {
            $url = parse_url($url);

            if (!is_array($url)) {
                throw new InvalidInputException("Invalid uri format");
            }

            $this->setScheme( $url["scheme"] ?? null );
            $this->setUserInfo( $url["user"] ?? null, $url["pass"] ?? null );
            $this->setHost( $url["host"] ?? null );
            $this->setPort( $url["port"] ?? 0 );
            $this->setPath( $url["path"] ?? null );
            $this->setQuerystring( $url["query"] ?? null );
            $this->setFragment( $url["fragment"] ?? null );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getFragment()}
     */
    function getFragment(): ?string {
        if (!empty($this->fragment)) {
            return $this->fragment;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::setFragment()}
     */
    function setFragment(string|null $fragment): void {
        if ($fragment !== null) {
            $fragment = parse_url("scheme://domain/#$fragment", PHP_URL_FRAGMENT);

            if (!is_string($fragment)) {
                throw new InvalidInputException("Invalid fragment format");
            }

            $fragment = rawurlencode(rawurldecode($fragment));
        }

        $this->fragment = $fragment;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getQuery()}
     */
    public function getQuery(string $name): string|null {
        return $this->query[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::setQuery()}
     */
    public function setQuery(string $name, string|null $value): void {
        if ($value !== null) {
            $this->query[$name] = $value;

        } else if (isset($this->query[$name])) {
            unset($this->query[$name]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getQuerystring()}
     */
    public function getQuerystring(): string|null {
        if (!empty($this->query)) {
            return http_build_query($this->query, "", null, PHP_QUERY_RFC3986);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::setQuerystring()}
     */
    public function setQuerystring(string|null $query): void {
        $this->query = [];
  
        if ($query !== null) {
            $query = parse_url("scheme://domain/?$query", PHP_URL_QUERY);

            if (!is_string($query)) {
                throw new InvalidInputException("Invalid query format");
            }

            parse_str($query, $result);

            /** @var array<string, string|list<string>> $result */
            foreach ($result as $key => $value) {
                if (is_array($value)) {
                    $value = $value[0] ?? null;
                    
                    if ($value === null) {
                        continue;
                    }
                }

                $this->query[$key] = rawurldecode($value);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getPath()}
     */
    public function getPath(): string|null {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::setPath()}
     */
    public function setPath(string|null $path): void {
        if ($path !== null) {
            $path = parse_url($path, \PHP_URL_PATH);
            
            if (!is_string($path)) {
                throw new InvalidInputException("Invalid path format");
            }
            
            $path = implode("/", array_map("rawurlencode", explode("/", rawurldecode($path))));
        }
        
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getPort()}
     */
    public function getPort(): int {
        if ($this->port == 0
                && ($scheme = $this->getScheme()) !== null) {

            if ($scheme == "http") {
                return 80;

            } else if ($scheme == "https") {
                return 443;
            }
        }

        return $this->port;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getPort()}
     */
    public function setPort(int $port): void {
        if ($port != 0) {
            $port = parse_url("scheme://domain:$port", PHP_URL_PORT);

            if (!is_int($port)) {
                throw new InvalidInputException("Invalid port number");
            }
        }

        $this->port = $port;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getHost()}
     */
    public function getHost(): ?string {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::setHost()}
     */
    public function setHost(string|null $host): void {
        if ($host !== null) {
            $host = parse_url("scheme://$host", PHP_URL_HOST);

            if (!is_string($host)) {
                throw new InvalidInputException("Invalid host format");
            }

            $host = strtolower($host);
        }

        $this->host = $host;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::isDefaultPort()}
     */
    public function isDefaultPort(): bool {
        $port = $this->getPort();
        $scheme = $this->getScheme();

        return ($port == 0
                || ($port == 80 && $scheme === "http")
                || ($port == 443 && $scheme === "https"));
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getAuthority()}
     */
    public function getAuthority(): string|null {
        $str = "";

        if (($host = $this->getHost()) === null) {
            $host = "127.0.0.1";
        }

        if (($user = $this->getUser()) !== null) {
            $str .= $user;

            if (($passwd = $this->getPassword()) !== null) {
                $str .= ":";
                $str .= $passwd;
            }
            
            $str .= "@";
        }
        
        if (($host = $this->getHost()) !== null) {
            $str .= $host;
        }

        if (!$this->isDefaultPort()) {
            $str .= ":";
            $str .= $this->getPort();
        }

        return empty($str) ? null : $str;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::setAuthority()}
     */
    public function setAuthority(string|null $auth): void {
        if ($auth !== null) {
            $auth = parse_url("scheme://$auth");

            if (!is_array($auth)) {
                 throw new InvalidInputException("Invalid authority format");
            }

            $this->setHost($auth["host"] ?? null);
            $this->setPort($auth["port"] ?? 0);
            $this->setUserInfo($auth["user"] ?? null, $auth["pass"] ?? null);

        } else {
            $this->setHost(null);
            $this->setPort(0);
            $this->setUserInfo(null);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getUser()}
     */
    public function getUser(): string|null {
        return $this->user;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getPassword()}
     */
    public function getPassword(): string|null {
        return $this->passwd;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::setUserInfo()}
     */
    public function setUserInfo(string|null $user, string|null $password = null): void {
        if ($user !== null) {
            $user = parse_url("scheme://$user:psw@domain", PHP_URL_USER);

            if (!is_string($user)) {
                throw new InvalidInputException("Invalid user info format");
            }

            if ($password !== null) {
                $password = parse_url("scheme://user:$password@domain", PHP_URL_PASS);

                if (!is_string($password)) {
                    throw new InvalidInputException("Invalid user info format");
                }
            }

        } else {
            $password = null;
        }

        $this->user = $user;
        $this->passwd = $password;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::getScheme()}
     */
    public function getScheme(): string|null {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see IUri::setScheme()}
     */
    public function setScheme(string|null $scheme): void {
        if ($scheme !== null) {
            $scheme = parse_url("$scheme://domain", PHP_URL_SCHEME);

            if (!is_string($scheme)) {
                throw new InvalidInputException("Invalid scheme format");
            }

            $scheme = strtolower($scheme);
        }

        $this->scheme = $scheme;
    }

    /**
     * {@inheritdoc}
     *
     * @override {@see Stringable::toString()}
     */
    public function toString(): string {
        $str = "";

        if (($scheme = $this->getScheme()) !== null) {
            $str .= $scheme;
            $str .= ":";
        }

        if (($authority = $this->getAuthority()) !== null) {
            $str .= "//";
            $str .= $authority;
        }

        if (($path = $this->getPath()) !== null) {
            if ($path[0] != "/" && $authority != null) {
                $str .= "/";
                $str .= $path;

            } else if (substr($path, 0, 2) == "//" && $authority == null) {
                $str .= "/";
                $str .= ltrim($path, "/");

            } else {
                $str .= $path;
            }
        }

        if (($query = $this->getQuerystring()) !== null) {
            $str .= "?";
            $str .= $query;
        }

        if (($fragment = $this->getFragment()) !== null) {
            $str .= "#";
            $str .= $fragment;
        }

        return $str;
    }

    /**
     * @ignore
     */
    public function __toString() {
        return $this->toString();
    }
}
