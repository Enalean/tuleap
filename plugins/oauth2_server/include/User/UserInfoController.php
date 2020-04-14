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

namespace Tuleap\OAuth2Server\User;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OpenIDConnectEmailScope;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OpenIDConnectProfileScope;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\User\OAuth2\ResourceServer\GrantedAuthorization;
use Tuleap\User\OAuth2\ResourceServer\OAuth2ResourceServerMiddleware;

final class UserInfoController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var JSONResponseBuilder
     */
    private $json_response_builder;

    public function __construct(
        JSONResponseBuilder $json_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->json_response_builder = $json_response_builder;
    }

    /**
     * @throws \JsonException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $granted_authorization = $request->getAttribute(OAuth2ResourceServerMiddleware::class);
        assert($granted_authorization instanceof GrantedAuthorization);

        $current_user       = $granted_authorization->getUser();
        $user_info_response = UserInfoResponseRepresentation::fromUserWithSubject($current_user);

        $email_scope = OpenIDConnectEmailScope::fromItself();
        $profile_scope = OpenIDConnectProfileScope::fromItself();
        foreach ($granted_authorization->getScopes() as $scope) {
            if ($email_scope->covers($scope)) {
                $user_info_response = $user_info_response->withEmail();
            }
            if ($profile_scope->covers($scope)) {
                $user_info_response = $user_info_response->withProfile();
            }
        }

        return $this->json_response_builder->fromData($user_info_response);
    }
}
