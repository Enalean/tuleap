<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Http\Server;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class NullServerRequest implements ServerRequestInterface
{
    private ServerRequest $server_request;

    public function __construct()
    {
        $this->server_request = new ServerRequest('GET', '/');
    }

    public function getProtocolVersion()
    {
        return $this->server_request->getProtocolVersion();
    }

    public function withProtocolVersion($version)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->server_request->withProtocolVersion($version);
    }

    public function getHeaders()
    {
        return $this->server_request->getHeaders();
    }

    public function hasHeader($name)
    {
        return $this->server_request->hasHeader($name);
    }

    public function getHeader($name)
    {
        return $this->server_request->getHeader($name);
    }

    public function getHeaderLine($name)
    {
        return $this->server_request->getHeaderLine($name);
    }

    public function withHeader($name, $value)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->server_request->withHeader($name, $value);
    }

    public function withAddedHeader($name, $value)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->server_request->withAddedHeader($name, $value);
    }

    public function withoutHeader($name)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->server_request->withoutHeader($name);
    }

    public function getBody()
    {
        return $this->server_request->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->server_request->withBody($body);
    }

    public function getRequestTarget()
    {
        return $this->server_request->getRequestTarget();
    }

    public function withRequestTarget($requestTarget)
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withRequestTarget($requestTarget);
    }

    public function getMethod()
    {
        return $this->server_request->getMethod();
    }

    public function withMethod($method)
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withMethod($method);
    }

    public function getUri()
    {
        return $this->server_request->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withUri($uri, $preserveHost);
    }

    public function getServerParams()
    {
        return $this->server_request->getServerParams();
    }

    public function getCookieParams()
    {
        return $this->server_request->getCookieParams();
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withCookieParams($cookies);
    }

    public function getQueryParams()
    {
        return $this->server_request->getQueryParams();
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withQueryParams($query);
    }

    public function getUploadedFiles()
    {
        return $this->server_request->getUploadedFiles();
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withUploadedFiles($uploadedFiles);
    }

    public function getParsedBody()
    {
        return $this->server_request->getParsedBody();
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withParsedBody($data);
    }

    public function getAttributes()
    {
        return $this->server_request->getAttributes();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->server_request->getAttribute($name, $default);
    }

    public function withAttribute($name, $value)
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withAttribute($name, $value);
    }

    public function withoutAttribute($name)
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withoutAttribute($name);
    }
}
