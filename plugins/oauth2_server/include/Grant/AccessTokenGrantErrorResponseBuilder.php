<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\Grant;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class AccessTokenGrantErrorResponseBuilder
{
    private const ERROR_CODE_INVALID_REQUEST = 'invalid_request';
    private const ERROR_CODE_INVALID_GRANT   = 'invalid_grant';
    private const ERROR_CODE_INVALID_CLIENT  = 'invalid_client';
    private const ERROR_CODE_INVALID_SCOPE   = 'invalid_scope';

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

    public function buildInvalidRequestResponse(): ResponseInterface
    {
        return $this->buildErrorResponse(self::ERROR_CODE_INVALID_REQUEST);
    }

    public function buildInvalidGrantResponse(): ResponseInterface
    {
        return $this->buildErrorResponse(self::ERROR_CODE_INVALID_GRANT);
    }

    public function buildInvalidClientResponse(): ResponseInterface
    {
        return $this->buildErrorResponse(self::ERROR_CODE_INVALID_CLIENT);
    }

    public function buildInvalidScopeResponse(): ResponseInterface
    {
        return $this->buildErrorResponse(self::ERROR_CODE_INVALID_SCOPE);
    }

    /**
     * @psalm-param self::ERROR_CODE_* $error_code See https://tools.ietf.org/html/rfc6749#section-5.2
     */
    private function buildErrorResponse(string $error_code): ResponseInterface
    {
        if ($error_code === self::ERROR_CODE_INVALID_CLIENT) {
            $response = $this->response_factory->createResponse(401)
                ->withHeader('WWW-Authenticate', 'Basic realm="Tuleap OAuth2 Token Endpoint"');
        } else {
            $response = $this->response_factory->createResponse(400);
        }
        return $response
            ->withHeader('Content-Type', AccessTokenGrantController::CONTENT_TYPE_RESPONSE)
            ->withBody(
                $this->stream_factory->createStream(json_encode(['error' => $error_code], JSON_THROW_ON_ERROR))
            );
    }
}
