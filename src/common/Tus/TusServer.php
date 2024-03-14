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

declare(strict_types=1);

namespace Tuleap\Tus;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TusServer implements RequestHandlerInterface
{
    private const TUS_VERSION = '1.0.0';

    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var TusDataStore
     */
    private $data_store;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        TusDataStore $data_store,
    ) {
        $this->response_factory = $response_factory;
        $this->data_store       = $data_store;
    }

    public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
    {
        $response = null;
        try {
            switch ($request->getMethod()) {
                case 'OPTIONS':
                    $response = $this->processOptions();
                    break;
                case 'HEAD':
                    $this->checkProtocolVersionIsSupported($request);
                    $file = $this->data_store->getFileInformationProvider()->getFileInformation($request);
                    if ($file !== null) {
                        $response = $this->processHead($file);
                    }
                    break;
                case 'PATCH':
                    $this->checkProtocolVersionIsSupported($request);
                    $file = $this->data_store->getFileInformationProvider()->getFileInformation($request);
                    if ($file !== null) {
                        $response = $this->processPatch($request, $file);
                    }
                    break;
                case 'DELETE':
                    $terminater = $this->data_store->getTerminater();
                    if ($terminater === null) {
                        return $this->getNonSupportedResponse();
                    }
                    $this->checkProtocolVersionIsSupported($request);
                    $file = $this->data_store->getFileInformationProvider()->getFileInformation($request);
                    if ($file !== null) {
                        $terminater->terminateUpload($file);
                        $response = $this->response_factory->createResponse(204);
                    }
                    break;
                default:
                    return $this->getNonSupportedResponse();
            }
        } catch (TusServerIncompatibleVersionException $exception) {
            $response = $this->response_factory->createResponse(
                $exception->getHTTPStatusCode(),
                $exception->getMessage()
            );
            $response = $response->withHeader('Tus-Version', self::TUS_VERSION);
        } catch (TusServerException $exception) {
            $response = $this->response_factory->createResponse(
                $exception->getHTTPStatusCode(),
                $exception->getMessage()
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

    private function getNonSupportedResponse(): ResponseInterface
    {
        return $this->response_factory->createResponse(405);
    }

    private function processOptions(): ResponseInterface
    {
        $response = $this->response_factory->createResponse(204)
            ->withHeader('Tus-Version', self::TUS_VERSION);

        $extensions = [];
        if ($this->data_store->getTerminater() !== null) {
            $extensions[] = 'termination';
        }

        if (! empty($extensions)) {
            $response = $response->withAddedHeader('Tus-Extension', \implode(',', $extensions));
        }

        return $response;
    }

    private function processHead(TusFileInformation $file_information): ResponseInterface
    {
        return $this->response_factory->createResponse(204)
            ->withHeader('Upload-Length', (string) $file_information->getLength())
            ->withHeader('Upload-Offset', (string) $file_information->getOffset())
            ->withHeader('Cache-Control', 'no-cache');
    }

    private function processPatch(\Psr\Http\Message\ServerRequestInterface $request, TusFileInformation $file_information): ResponseInterface
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

        $incoming_stream = $request->getBody()->detach();
        if ($incoming_stream === null) {
            throw new CannotReadIncomingFileException();
        }

        $copied_size = 0;
        $locker      = $this->data_store->getLocker();
        try {
            if ($locker !== null && ! $locker->lock($file_information)) {
                return $this->response_factory->createResponse(423);
            }

            if ((int) $request->getHeaderLine('Upload-Offset') !== $file_information->getOffset()) {
                return $this->response_factory->createResponse(409);
            }

            $copied_size = $this->data_store->getWriter()->writeChunk(
                $file_information,
                $file_information->getOffset(),
                $incoming_stream
            );
        } finally {
            if ($locker !== null) {
                $locker->unlock($file_information);
            }
        }

        $finisher           = $this->data_store->getFinisher();
        $is_upload_finished = ($file_information->getOffset() + $copied_size) >= $file_information->getLength();
        if ($finisher !== null && $is_upload_finished) {
            $finisher->finishUpload($request, $file_information);
        }

        return $this->response_factory->createResponse(204)
            ->withHeader('Upload-Offset', (string) ($file_information->getOffset() + $copied_size));
    }

    /**
     * @throws TusServerIncompatibleVersionException
     */
    private function checkProtocolVersionIsSupported(\Psr\Http\Message\RequestInterface $request): void
    {
        if ($request->getHeaderLine('Tus-Resumable') !== self::TUS_VERSION) {
            throw new TusServerIncompatibleVersionException(self::TUS_VERSION);
        }
    }
}
