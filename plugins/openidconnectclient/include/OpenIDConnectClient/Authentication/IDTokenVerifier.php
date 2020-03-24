<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

use Tuleap\OpenIDConnectClient\Provider\Provider;

class IDTokenVerifier
{
    /**
     * @var IssuerClaimValidator
     */
    private $issuer_claim_validator;

    public function __construct(IssuerClaimValidator $issuer_claim_validator)
    {
        $this->issuer_claim_validator = $issuer_claim_validator;
    }

    /**
     * @return array
     * @throws MalformedIDTokenException
     */
    public function validate(Provider $provider, $nonce, $encoded_id_token)
    {
        $id_token = $this->getJWTPayload($encoded_id_token);

        if (! $this->isSubjectIdentifierClaimPresent($id_token) ||
            ! isset($id_token['iss']) ||
            ! $this->issuer_claim_validator->isIssuerClaimValid($provider, $id_token['iss']) ||
            ! $this->isAudienceClaimValid($provider->getClientId(), $id_token) ||
            ! $this->isNonceValid($nonce, $id_token)
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
            $payload = json_decode(sodium_base642bin($encoded_payload, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException | \SodiumException $ex) {
            throw new MalformedIDTokenException('ID token parts must be an URL safe base64 encoded JSON');
        }

        return (array) $payload;
    }

    private function isSubjectIdentifierClaimPresent(array $id_token)
    {
        return isset($id_token['sub']);
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

    /**
     * @return bool
     */
    private function isNonceValid($nonce, array $id_token)
    {
        if (! isset($id_token['nonce'])) {
            return false;
        }
        return hash_equals($nonce, $id_token['nonce']);
    }
}
