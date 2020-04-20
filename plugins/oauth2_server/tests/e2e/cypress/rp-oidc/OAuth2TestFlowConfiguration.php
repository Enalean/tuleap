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

namespace Tuleap\OAuth2Server\E2E\RelyingPartyOIDC;

/**
 * @psalm-immutable
 */
final class OAuth2TestFlowConfiguration
{
    /**
     * @var string
     */
    private $authorization_endpoint;
    /**
     * @var string
     */
    private $token_endpoint;
    /**
     * @var string
     */
    private $userinfo_endpoint;
    /**
     * @var string
     */
    private $jwks_uri;

    public function __construct(
        string $authorization_endpoint,
        string $token_endpoint,
        string $userinfo_endpoint,
        string $jwks_uri
    ) {
        $this->authorization_endpoint = $authorization_endpoint;
        $this->token_endpoint         = $token_endpoint;
        $this->userinfo_endpoint      = $userinfo_endpoint;
        $this->jwks_uri               = $jwks_uri;
    }

    public function getAuthorizationEndpoint(): string
    {
        return $this->authorization_endpoint;
    }

    public function getTokenEndpoint(): string
    {
        return $this->token_endpoint;
    }

    public function getUserinfoEndpoint(): string
    {
        return $this->userinfo_endpoint;
    }

    public function getJwksUri(): string
    {
        return $this->jwks_uri;
    }
}
