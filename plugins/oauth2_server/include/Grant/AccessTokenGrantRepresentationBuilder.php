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

use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenCreator;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCode;
use Tuleap\OAuth2Server\OpenIDConnect\IDToken\OpenIDConnectIDTokenCreator;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshToken;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenCreator;

class AccessTokenGrantRepresentationBuilder
{
    /**
     * @var OAuth2AccessTokenCreator
     */
    private $access_token_creator;
    /**
     * @var OAuth2RefreshTokenCreator
     */
    private $refresh_token_creator;
    /**
     * @var OpenIDConnectIDTokenCreator
     */
    private $id_token_creator;

    public function __construct(
        OAuth2AccessTokenCreator $access_token_creator,
        OAuth2RefreshTokenCreator $refresh_token_creator,
        OpenIDConnectIDTokenCreator $id_token_creator
    ) {
        $this->access_token_creator  = $access_token_creator;
        $this->refresh_token_creator = $refresh_token_creator;
        $this->id_token_creator      = $id_token_creator;
    }

    public function buildRepresentationFromAuthorizationCode(
        \DateTimeImmutable $current_time,
        OAuth2App $app,
        OAuth2AuthorizationCode $authorization_code
    ): OAuth2AccessTokenSuccessfulRequestRepresentation {
        $access_token = $this->access_token_creator->issueAccessToken(
            $current_time,
            $authorization_code->getID(),
            $authorization_code->getScopes()
        );
        $refresh_token = $this->refresh_token_creator->issueRefreshTokenIdentifierFromAuthorizationCode($current_time, $authorization_code);
        $id_token      = $this->id_token_creator->issueIDTokenFromAuthorizationCode($current_time, $app, $authorization_code);

        return OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshTokenWithUserAuthentication(
            $access_token,
            $refresh_token,
            $id_token,
            $current_time,
        );
    }

    public function buildRepresentationFromRefreshToken(
        \DateTimeImmutable $current_time,
        OAuth2RefreshToken $refresh_token
    ): OAuth2AccessTokenSuccessfulRequestRepresentation {
        $access_token = $this->access_token_creator->issueAccessToken(
            $current_time,
            $refresh_token->getAssociatedAuthorizationCodeID(),
            $refresh_token->getScopes()
        );
        $new_refresh_token_identifier = $this->refresh_token_creator->issueRefreshTokenIdentifierFromExistingRefreshToken(
            $current_time,
            $refresh_token
        );

        return OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
            $access_token,
            $new_refresh_token_identifier,
            $current_time
        );
    }
}
