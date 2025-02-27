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
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
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

    public function getProtocolVersion(): string
    {
        return $this->server_request->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        return $this->server_request->withProtocolVersion($version);
    }

    public function getHeaders(): array
    {
        return $this->server_request->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->server_request->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->server_request->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->server_request->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->server_request->withHeader($name, $value);
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->server_request->withAddedHeader($name, $value);
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return $this->server_request->withoutHeader($name);
    }

    public function getBody(): StreamInterface
    {
        return $this->server_request->getBody();
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->server_request->withBody($body);
    }

    public function getRequestTarget(): string
    {
        return $this->server_request->getRequestTarget();
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        return $this->server_request->withRequestTarget($requestTarget);
    }

    public function getMethod(): string
    {
        return $this->server_request->getMethod();
    }

    public function withMethod(string $method): RequestInterface
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withMethod($method);
    }

    public function getUri(): UriInterface
    {
        return $this->server_request->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withUri($uri, $preserveHost);
    }

    public function getServerParams(): array
    {
        return $this->server_request->getServerParams();
    }

    public function getCookieParams(): array
    {
        return $this->server_request->getCookieParams();
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return $this->server_request->withCookieParams($cookies);
    }

    public function getQueryParams(): array
    {
        return $this->server_request->getQueryParams();
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        return $this->server_request->withQueryParams($query);
    }

    public function getUploadedFiles(): array
    {
        return $this->server_request->getUploadedFiles();
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return $this->server_request->withUploadedFiles($uploadedFiles);
    }

    public function getParsedBody()
    {
        return $this->server_request->getParsedBody();
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        return $this->server_request->withParsedBody($data);
    }

    public function getAttributes(): array
    {
        return $this->server_request->getAttributes();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->server_request->getAttribute($name, $default);
    }

    public function withAttribute($name, $value): ServerRequestInterface
    {
        return $this->server_request->withAttribute($name, $value);
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        return $this->server_request->withoutAttribute($name);
    }
}
