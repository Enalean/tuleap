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

    #[\Override]
    public function getProtocolVersion(): string
    {
        return $this->server_request->getProtocolVersion();
    }

    #[\Override]
    public function withProtocolVersion(string $version): MessageInterface
    {
        return $this->server_request->withProtocolVersion($version);
    }

    #[\Override]
    public function getHeaders(): array
    {
        return $this->server_request->getHeaders();
    }

    #[\Override]
    public function hasHeader(string $name): bool
    {
        return $this->server_request->hasHeader($name);
    }

    #[\Override]
    public function getHeader(string $name): array
    {
        return $this->server_request->getHeader($name);
    }

    #[\Override]
    public function getHeaderLine(string $name): string
    {
        return $this->server_request->getHeaderLine($name);
    }

    #[\Override]
    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->server_request->withHeader($name, $value);
    }

    #[\Override]
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->server_request->withAddedHeader($name, $value);
    }

    #[\Override]
    public function withoutHeader(string $name): MessageInterface
    {
        return $this->server_request->withoutHeader($name);
    }

    #[\Override]
    public function getBody(): StreamInterface
    {
        return $this->server_request->getBody();
    }

    #[\Override]
    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->server_request->withBody($body);
    }

    #[\Override]
    public function getRequestTarget(): string
    {
        return $this->server_request->getRequestTarget();
    }

    #[\Override]
    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        return $this->server_request->withRequestTarget($requestTarget);
    }

    #[\Override]
    public function getMethod(): string
    {
        return $this->server_request->getMethod();
    }

    #[\Override]
    public function withMethod(string $method): RequestInterface
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withMethod($method);
    }

    #[\Override]
    public function getUri(): UriInterface
    {
        return $this->server_request->getUri();
    }

    #[\Override]
    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->server_request->withUri($uri, $preserveHost);
    }

    #[\Override]
    public function getServerParams(): array
    {
        return $this->server_request->getServerParams();
    }

    #[\Override]
    public function getCookieParams(): array
    {
        return $this->server_request->getCookieParams();
    }

    #[\Override]
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return $this->server_request->withCookieParams($cookies);
    }

    #[\Override]
    public function getQueryParams(): array
    {
        return $this->server_request->getQueryParams();
    }

    #[\Override]
    public function withQueryParams(array $query): ServerRequestInterface
    {
        return $this->server_request->withQueryParams($query);
    }

    #[\Override]
    public function getUploadedFiles(): array
    {
        return $this->server_request->getUploadedFiles();
    }

    #[\Override]
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return $this->server_request->withUploadedFiles($uploadedFiles);
    }

    #[\Override]
    public function getParsedBody()
    {
        return $this->server_request->getParsedBody();
    }

    #[\Override]
    public function withParsedBody($data): ServerRequestInterface
    {
        return $this->server_request->withParsedBody($data);
    }

    #[\Override]
    public function getAttributes(): array
    {
        return $this->server_request->getAttributes();
    }

    #[\Override]
    public function getAttribute($name, $default = null)
    {
        return $this->server_request->getAttribute($name, $default);
    }

    #[\Override]
    public function withAttribute($name, $value): ServerRequestInterface
    {
        return $this->server_request->withAttribute($name, $value);
    }

    #[\Override]
    public function withoutAttribute($name): ServerRequestInterface
    {
        return $this->server_request->withoutAttribute($name);
    }
}
