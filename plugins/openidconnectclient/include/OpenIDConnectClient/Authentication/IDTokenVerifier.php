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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Authentication;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class IDTokenVerifier
{
    private const LEEWAY_IN_SECOND = 10;

    /**
     * @var Parser
     */
    private $parser;
    /**
     * @var IssuerClaimValidator
     */
    private $issuer_claim_validator;
    /**
     * @var JWKSKeyFetcher
     */
    private $jwks_key_fetcher;
    /**
     * @var Sha256
     */
    private $signer;

    public function __construct(
        Parser $parser,
        IssuerClaimValidator $issuer_claim_validator,
        JWKSKeyFetcher $jwks_key_fetcher,
        Sha256 $signer
    ) {
        $this->parser                 = $parser;
        $this->issuer_claim_validator = $issuer_claim_validator;
        $this->jwks_key_fetcher       = $jwks_key_fetcher;
        $this->signer                 = $signer;
    }

    /**
     * @throws MalformedIDTokenException
     */
    public function validate(Provider $provider, string $nonce, string $encoded_id_token): string
    {
        try {
            $id_token = $this->parser->parse($encoded_id_token);
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            throw new MalformedIDTokenException($exception->getMessage(), 0, $exception);
        }

        $validation_data = new ValidationData(null, self::LEEWAY_IN_SECOND);

        try {
            $sub_claim = $id_token->getClaim('sub');
        } catch (\OutOfBoundsException $exception) {
            throw new MalformedIDTokenException('sub claim is not present', 0, $exception);
        }

        if (
            ! is_string($sub_claim) ||
            ! $id_token->validate($validation_data) ||
            ! $this->isNonceValid($nonce, $id_token) ||
            ! $this->isAudienceClaimValid($provider->getClientId(), $id_token) ||
            ! $this->issuer_claim_validator->isIssuerClaimValid($provider, $id_token->getClaim('iss', ''))
        ) {
            throw new MalformedIDTokenException('ID token claims are not valid');
        }

        if (! $this->verifySignature($provider, $id_token)) {
            throw new MalformedIDTokenException('ID token signature is not valid');
        }

        return $sub_claim;
    }

    private function isAudienceClaimValid(string $provider_client_id, Token $id_token): bool
    {
        $audience_claim = $id_token->getClaim('aud');

        if (is_string($audience_claim)) {
            return $provider_client_id === $audience_claim;
        }

        if (is_array($audience_claim)) {
            return in_array($provider_client_id, $audience_claim, true);
        }

        return false;
    }

    private function isNonceValid(string $nonce, Token $id_token): bool
    {
        return hash_equals($nonce, $id_token->getClaim('nonce', ''));
    }

    private function verifySignature(Provider $provider, Token $id_token): bool
    {
        $keys_pem_format = $this->jwks_key_fetcher->fetchKey($provider);

        if ($keys_pem_format === null) {
            return true;
        }

        foreach ($keys_pem_format as $key_pem_format) {
            if ($id_token->verify($this->signer, $key_pem_format)) {
                return true;
            }
        }

        return false;
    }
}
