<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Http\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Tuleap\Http\Response\Stream\CallbackNoBufferStream;
use function is_resource;

final class BinaryFileResponseBuilder
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;

    public function __construct(ResponseFactoryInterface $response_factory, StreamFactoryInterface $stream_factory)
    {
        $this->response_factory = $response_factory;
        $this->stream_factory   = $stream_factory;
    }

    public function fromFilePath(ServerRequestInterface $request, string $file_path, string $name = '', string $content_type = 'application/octet-stream'): ResponseInterface
    {
        $file_resource = @fopen($file_path, 'rb');
        if (! is_resource($file_resource)) {
            throw new RuntimeException("Not able to read $file_path");
        }
        $file_name = $name;
        if ($file_name === '') {
            $file_name = basename($file_path);
        }
        return $this->build($request, $this->stream_factory->createStreamFromResource($file_resource), $file_name, $content_type);
    }

    /**
     * @psalm-param callable():void $callback
     */
    public function fromCallback(ServerRequestInterface $request, callable $callback, string $name, string $content_type): ResponseInterface
    {
        return $this->build($request, new CallbackNoBufferStream($callback), $name, $content_type);
    }

    private function build(ServerRequestInterface $request, StreamInterface $stream, string $name, string $content_type): ResponseInterface
    {
        $response = $this->response_factory->createResponse()
            ->withHeader('Content-Type', $content_type)
            ->withHeader('Content-Disposition', 'attachment; filename="' . $this->getNameForContentDispositionHeader($name) . '"')
            ->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; form-action 'none';")
            ->withHeader('X-DNS-Prefetch-Control', 'off')
            ->withHeader('Cache-Control', 'private')
            ->withHeader('Pragma', 'no-cache')
            ->withBody($stream);

        $length = $stream->getSize();
        if ($length !== null) {
            $response = $response->withHeader('Content-Length', (string) $length);
        }

        return $this->handleRange($request, $response);
    }

    private function getNameForContentDispositionHeader(string $name): string
    {
        return str_replace('"', '\\"', $this->removeNonPrintableASCIIChars($name));
    }

    private function removeNonPrintableASCIIChars(string $str): string
    {
        return preg_replace('/[^(\x20-\x7F)]*/', '', $str);
    }

    private function handleRange(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (! $response->hasHeader('Content-Length')) {
            return $response;
        }
        $response = $response->withHeader('Accept-Ranges', 'bytes');

        $range_header = $request->getHeaderLine('Range');
        if ($range_header === '') {
            return $response;
        }

        if (preg_match('/bytes=(?P<start>\d+)-(?P<end>\d+)?/', $range_header, $matches) !== 1) {
            return $response;
        }

        $size        = $response->getBody()->getSize() ?? 0;
        $range_start = (int) $matches['start'];
        $range_end   = $size - 1;
        if (isset($matches['end'])) {
            $range_end = (int) $matches['end'];
        }

        if ($range_end > $size - 1) {
            return $response;
        }

        return $response->withStatus(206)
            ->withHeader('Content-Length', (string) ($range_end - $range_start + 1))
            ->withHeader('Content-Range', "bytes $range_start-$range_end/$size");
    }
}
