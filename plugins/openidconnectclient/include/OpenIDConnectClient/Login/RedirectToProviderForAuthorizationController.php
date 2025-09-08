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

namespace Tuleap\OpenIDConnectClient\Login;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationRequestCreator;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class RedirectToProviderForAuthorizationController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var ProviderManager
     */
    private $provider_manager;
    /**
     * @var AuthorizationRequestCreator
     */
    private $authorization_request_creator;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        ProviderManager $provider_manager,
        AuthorizationRequestCreator $authorization_request_creator,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory              = $response_factory;
        $this->provider_manager              = $provider_manager;
        $this->authorization_request_creator = $authorization_request_creator;
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $provider_id = $request->getAttribute('provider_id');

        try {
            $provider = $this->provider_manager->getById($provider_id);
        } catch (ProviderNotFoundException $exception) {
            throw new NotFoundException('', $exception);
        }

        if (! $provider->isUniqueAuthenticationEndpoint() && $this->provider_manager->isAProviderConfiguredAsUniqueAuthenticationEndpoint()) {
            throw new ForbiddenException();
        }

        $query_params = $request->getQueryParams();
        $return_to    = null;
        if (isset($query_params['return_to']) && is_string($query_params['return_to'])) {
            $return_to = $query_params['return_to'];
        }

        $authorization_request = $this->authorization_request_creator->createAuthorizationRequest(
            $provider,
            $return_to
        );


        return $this->response_factory->createResponse(302)->withHeader('Location', $authorization_request->getURL());
    }
}
