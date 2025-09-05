<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\OAuth2;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationCodeResponseFactory;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationEndpointController;
use Tuleap\OAuth2ServerCore\AuthorizationServer\ConsentRequiredResponseBuilder;

final class RejectAuthorizationRequiringConsent implements ConsentRequiredResponseBuilder
{
    public function __construct(private AuthorizationCodeResponseFactory $response_factory, private LoggerInterface $logger)
    {
    }

    #[\Override]
    public function buildConsentRequiredResponse(
        ServerRequestInterface $request,
        OAuth2App $client_app,
        string $redirect_uri,
        ?string $state_value,
        ?string $code_challenge,
        ?string $oidc_nonce,
        array $scopes,
    ): ResponseInterface {
        $this->logger->info('OAuth2 MediaWiki authorization is not supposed to require consent, check if the requested scopes are allowed for this context');
        return $this->response_factory->createErrorResponse(
            AuthorizationEndpointController::ERROR_CODE_INVALID_REQUEST,
            $redirect_uri,
            $state_value
        );
    }
}
