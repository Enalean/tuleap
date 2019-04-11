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

use function is_resource;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

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

    public function fromFilePath(string $file_path, string $name = '', string $content_type = 'application/octet-stream') : ResponseInterface
    {
        $file_resource = @fopen($file_path, 'rb');
        if (! is_resource($file_resource)) {
            throw new RuntimeException("Not able to read $file_path");
        }
        $file_name = $name;
        if ($file_name === '') {
            $file_name = basename($file_path);
        }
        return $this->build($file_resource, $file_name, $content_type, filesize($file_path));
    }

    private function build($resource, string $name, string $content_type, int $length) : ResponseInterface
    {
        return $this->response_factory->createResponse()
            ->withHeader('Content-Length', (string) $length)
            ->withHeader('Content-Type', $content_type)
            ->withHeader('Content-Disposition', 'attachment; filename="'. $this->getNameForContentDispositionHeader($name) .'"')
            ->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; form-action 'none';")
            ->withHeader('X-DNS-Prefetch-Control', 'off')
            ->withBody($this->stream_factory->createStreamFromResource($resource));
    }

    private function getNameForContentDispositionHeader(string $name) : string
    {
        return str_replace('"', '\\"', $this->removeNonPrintableASCIIChars($name));
    }

    private function removeNonPrintableASCIIChars(string $str) : string
    {
        return preg_replace('/[^(\x20-\x7F)]*/', '', $str);
    }
}
