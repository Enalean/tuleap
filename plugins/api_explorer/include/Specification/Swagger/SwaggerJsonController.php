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

namespace Tuleap\APIExplorer\Specification\Swagger;

use Codendi_HTMLPurifier;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\REST\RestlerFactory;
use Tuleap\REST\Specification\Swagger\SwaggerJsonSecurityDefinitionsCollection;

final class SwaggerJsonController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    private const API_VERSION = 1;

    /**
     * @var RestlerFactory
     */
    private $restler_factory;
    /**
     * @var string
     */
    private $version;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $html_purifier;
    /**
     * @var JSONResponseBuilder
     */
    private $json_response_builder;

    public function __construct(
        RestlerFactory $restler_factory,
        string $version,
        EventDispatcherInterface $event_dispatcher,
        Codendi_HTMLPurifier $html_purifier,
        JSONResponseBuilder $json_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        $this->restler_factory       = $restler_factory;
        $this->version               = $version;
        $this->event_dispatcher      = $event_dispatcher;
        $this->html_purifier         = $html_purifier;
        $this->json_response_builder = $json_response_builder;
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request_uri = $request->getUri();
        $swagger_host = $request_uri->getHost();
        $port         = $request_uri->getPort();
        if ($port !== null) {
            $swagger_host .= ':' . $port;
        }

        $restler = $this->restler_factory->buildRestler(self::API_VERSION);

        $security_definitions = $this->event_dispatcher->dispatch(
            new SwaggerJsonSecurityDefinitionsCollection()
        )->getSecurityDefinitions();

        $swagger = new SwaggerJson(
            $swagger_host,
            $restler->getWritableMimeTypes(),
            $restler->getReadableMimeTypes(),
            SwaggerJsonInfo::fromVersion($this->version),
            new SwaggerJsonPathsAndDefinitions(self::API_VERSION, $security_definitions, $this->html_purifier),
            $security_definitions
        );

        return $this->json_response_builder->fromData($swagger);
    }
}
