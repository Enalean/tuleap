<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Tus;

use Http\Message\ResponseFactory;
use Tuleap\Http\Server\RequestHandlerInterface;

final class TusServer implements RequestHandlerInterface
{
    const TUS_VERSION = '1.0.0';

    /**
     * @var ResponseFactory
     */
    private $response_factory;
    /**
     * @var TusFileProvider
     */
    private $file_provider;

    public function __construct(ResponseFactory $response_factory, TusFileProvider $file_provider)
    {
        $this->response_factory = $response_factory;
        $this->file_provider = $file_provider;
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $response = null;
        try {
            switch ($request->getMethod()) {
                case 'OPTIONS':
                    $response = $this->processOptions();
                    break;
                case 'HEAD':
                    $this->checkProtocolVersionIsSupported($request);
                    $file = $this->file_provider->getFile($request);
                    if ($file !== null) {
                        $response = $this->processHead($file);
                    }
                    break;
                case 'PATCH':
                    $this->checkProtocolVersionIsSupported($request);
                    $file = $this->file_provider->getFile($request);
                    if ($file !== null) {
                        $response = $this->processPatch($request, $file);
                    }
                    break;
                default:
                    return $this->response_factory->createResponse(405);
            }
        } catch (TusServerIncompatibleVersionException $exception) {
            $response = $this->response_factory->createResponse(
                $exception->getCode(),
                $exception->getMessage(),
                ['Tus-Version' => self::TUS_VERSION]
            );
        } catch (TusServerException $exception) {
            $response = $this->response_factory->createResponse(
                $exception->getCode(),
                $exception->getMessage(),
                ['Tus-Resumable' => self::TUS_VERSION]
            );
        }

        if ($response === null) {
            $response = $this->response_factory->createResponse(404);
        }

        if ($request->getMethod() === 'OPTIONS') {
            return $response;
        }
        return $response->withHeader('Tus-Resumable', self::TUS_VERSION);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function processOptions()
    {
        return $this->response_factory->createResponse(204)
            ->withHeader('Tus-Version', self::TUS_VERSION);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function processHead(TusFile $file)
    {
        return $this->response_factory->createResponse(204)
            ->withHeader('Upload-Length', $file->getLength())
            ->withHeader('Upload-Offset', $file->getOffset())
            ->withHeader('Cache-Control', 'no-cache');
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function processPatch(\Psr\Http\Message\RequestInterface $request, TusFile $file)
    {
        $content_type_header         = $request->getHeaderLine('Content-Type');
        $found_expected_content_type = false;
        foreach (explode(';', $content_type_header) as $content_type_part) {
            if (trim($content_type_part) === 'application/offset+octet-stream') {
                $found_expected_content_type = true;
                break;
            }
        }
        if (! $found_expected_content_type) {
            return $this->response_factory->createResponse(415);
        }

        if (! $request->hasHeader('Upload-Offset')) {
            return $this->response_factory->createResponse(400);
        }

        if ((int) $request->getHeaderLine('Upload-Offset') !== $file->getOffset()) {
            return $this->response_factory->createResponse(409);
        }

        $max_size_to_copy = $file->getLength() - $file->getOffset();
        $copied_size      = stream_copy_to_stream($request->getBody()->detach(), $file->getStream(), $max_size_to_copy);
        if ($copied_size === false) {
            throw new CannotWriteFileException();
        }

        return $this->response_factory->createResponse(204)
            ->withHeader('Upload-Offset', $file->getOffset() + $copied_size);
    }

    /**
     * @throws TusServerIncompatibleVersionException
     */
    private function checkProtocolVersionIsSupported(\Psr\Http\Message\RequestInterface $request)
    {
        if ($request->getHeaderLine('Tus-Resumable') !== self::TUS_VERSION) {
            throw new TusServerIncompatibleVersionException(self::TUS_VERSION);
        }
    }
}
