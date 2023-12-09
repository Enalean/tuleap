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

use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Tuleap\OpenIDConnectClient\Provider\Provider;

/**
 * @psalm-type AcceptableIssuerClaimValidator = AzureProviderIssuerClaimValidator|GenericProviderIssuerClaimValidator
 */
class IDTokenVerifier
{
    private const LEEWAY_DATE_INTERVAL = 'PT10S';

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
    /**
     * @var Validator
     */
    private $jwt_validator;

    /**
     * @param AcceptableIssuerClaimValidator $issuer_claim_validator
     */
    public function __construct(
        Parser $parser,
        IssuerClaimValidator $issuer_claim_validator,
        JWKSKeyFetcher $jwks_key_fetcher,
        Sha256 $signer,
        Validator $jwt_validator,
    ) {
        $this->parser                 = $parser;
        $this->issuer_claim_validator = $issuer_claim_validator;
        $this->jwks_key_fetcher       = $jwks_key_fetcher;
        $this->signer                 = $signer;
        $this->jwt_validator          = $jwt_validator;
    }

    /**
     * @throws MalformedIDTokenException
     */
    public function validate(Provider $provider, string $nonce, string $encoded_id_token): string
    {
        if ($encoded_id_token === '') {
            throw new MalformedIDTokenException('The encoded ID Token cannot be an empty string');
        }
        try {
            $id_token = $this->parser->parse($encoded_id_token);
            assert($id_token instanceof UnencryptedToken);
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            throw new MalformedIDTokenException($exception->getMessage(), 0, $exception);
        }

        $sub_claim = $id_token->claims()->get('sub');
        if (! is_string($sub_claim)) {
            throw new MalformedIDTokenException(sprintf('sub claim is not present or malformed (got %s)', gettype($sub_claim)));
        }

        if (! $this->jwt_validator->validate($id_token, new LooseValidAt(new FrozenClock(new \DateTimeImmutable()), new \DateInterval(self::LEEWAY_DATE_INTERVAL)))) {
            self::throwsInvalidIDTokenClaims(sprintf('the token is outside its validity period, including a leeway of %s', self::LEEWAY_DATE_INTERVAL));
        }

        if (! $this->isNonceValid($nonce, $id_token)) {
            self::throwsInvalidIDTokenClaims('nonce is not valid');
        }

        if (! $this->isAudienceClaimValid($provider->getClientId(), $id_token)) {
            self::throwsInvalidIDTokenClaims('audience claim is not valid');
        }

        if (! $this->issuer_claim_validator->isIssuerClaimValid($provider, $id_token->claims()->get('iss', '') ?? '')) {
            self::throwsInvalidIDTokenClaims('issuer claim is not valid');
        }

        if (! $this->verifySignature($provider, $id_token)) {
            throw new MalformedIDTokenException('ID token signature is not valid');
        }

        return $sub_claim;
    }

    private function isAudienceClaimValid(string $provider_client_id, Token $id_token): bool
    {
        if ($provider_client_id === '') {
            return false;
        }
        return $id_token->isPermittedFor($provider_client_id);
    }

    private function isNonceValid(string $nonce, UnencryptedToken $id_token): bool
    {
        return hash_equals($nonce, $id_token->claims()->get('nonce', '') ?? '');
    }

    private function verifySignature(Provider $provider, Token $id_token): bool
    {
        $keys_pem_format = $this->jwks_key_fetcher->fetchKey($provider);

        if ($keys_pem_format === null) {
            return true;
        }

        foreach ($keys_pem_format as $key_pem_format) {
            if ($key_pem_format === '') {
                continue;
            }
            if ($this->jwt_validator->validate($id_token, new SignedWith($this->signer, InMemory::plainText($key_pem_format)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws MalformedIDTokenException
     */
    private static function throwsInvalidIDTokenClaims(string $reason): void
    {
        throw new MalformedIDTokenException(sprintf('ID token claims are not valid (%s)', $reason));
    }
}
