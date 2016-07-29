<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication;

use Firebase\JWT\JWT;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class IDTokenVerifier
{
    /**
     * @return array
     * @throws MalformedIDTokenException
     */
    public function validate(Provider $provider, $encoded_id_token)
    {
        $id_token = $this->getJWTPayload($encoded_id_token);

        if (! $this->isSubjectIdentifierClaimPresent($id_token) ||
            ! $this->isIssuerClaimValid($provider->getAuthorizationEndpoint(), $id_token) ||
            ! $this->isAudienceClaimValid($provider->getClientId(), $id_token)
        ) {
            throw new MalformedIDTokenException('ID token claims are not valid');
        }

        return $id_token;
    }

    /**
     * @return array
     * @throws MalformedIDTokenException
     */
    private function getJWTPayload($encoded_id_token)
    {
        $jwt_parts = explode('.', $encoded_id_token);
        if (count($jwt_parts) !== 3) {
            throw new MalformedIDTokenException('ID token must composed of 3 parts');
        }

        $encoded_payload = $jwt_parts[1];

        try {
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($encoded_payload));
        } catch (\DomainException $ex) {
            throw new MalformedIDTokenException('ID token parts must be an URL safe base64 encoded JSON');
        }

        return (array) $payload;
    }

    private function isSubjectIdentifierClaimPresent(array $id_token)
    {
        return isset($id_token['sub']);
    }

    private function isIssuerClaimValid($provider_authorization_endpoint, array $id_token)
    {
        if (! isset($id_token['iss'])) {
            return false;
        }
        /*
         * OpenID Connect Core Standard said the issuer identifier must exactly match
         * the iss claim. However, since we do not implement OpenID Connect Discovery
         * the issuer identifier is not obtained so we do the next best things we can
         * do for now: we check if the iss claim is present in the authorization endpoint
         */
        return strpos($provider_authorization_endpoint, $id_token['iss']) !== false;
    }

    /**
     * @return bool
     */
    private function isAudienceClaimValid($provider_client_id, array $id_token)
    {
        if (! isset($id_token['aud'])) {
            return false;
        }
        return $id_token['aud'] === $provider_client_id ||
            (is_array($id_token['aud']) && in_array($provider_client_id, $id_token['aud']));
    }
}
