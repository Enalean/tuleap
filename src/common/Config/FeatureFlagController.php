<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Config;

use ForgeConfig;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Request\DispatchablePSR15Compatible;

final class FeatureFlagController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly ResponseFactoryInterface $response_factory,
        private readonly StreamFactoryInterface $stream_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        if (! isset($params['name'])) {
            return $this->formatErrorResponse('Bad request: the query param "name" is missing');
        }

        $feature_flag_name = $params['name'];
        if (! str_starts_with($feature_flag_name, ForgeConfig::FEATURE_FLAG_PREFIX)) {
            return $this->formatErrorResponse('Bad request: the name given is not a feature flag');
        }

        $feature_flag_value = ForgeConfig::get($feature_flag_name, false);

        if ($feature_flag_value === false) {
            return $this->formatErrorResponse('Bad request: the feature flag is not set');
        }

        return $this->response_factory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8')->withBody(
            $this->stream_factory->createStream(json_encode(['value' => $feature_flag_value], JSON_THROW_ON_ERROR))
        );
    }

    private function formatErrorResponse(string $message): ResponseInterface
    {
        return $this->response_factory->createResponse(400)->withHeader('Content-Type', 'application/json; charset=utf-8')->withBody(
            $this->stream_factory->createStream(json_encode(['error' => ['message' => $message]], JSON_THROW_ON_ERROR))
        );
    }
}
