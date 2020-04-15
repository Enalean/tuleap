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

namespace Tuleap\OAuth2Server\OpenIDConnect\Discovery;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

/**
 *
 * @see https://openid.net/specs/openid-connect-discovery-1_0.html
 */
final class DiscoveryController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var ConfigurationResponseRepresentationBuilder
     */
    private $representation_builder;
    /**
     * @var JSONResponseBuilder
     */
    private $json_response_builder;

    public function __construct(
        ConfigurationResponseRepresentationBuilder $representation_builder,
        JSONResponseBuilder $json_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->representation_builder = $representation_builder;
        $this->json_response_builder = $json_response_builder;
    }

    /**
     * @throws \JsonException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json_response_builder->fromData($this->representation_builder->build());
    }
}
