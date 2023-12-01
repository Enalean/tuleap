<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Validator;
use Tuleap\OpenIDConnectClient\Provider\Provider;

final class IDTokenVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var resource
     */
    private static $rsa_key;

    private IDTokenVerifier $id_token_verifier;

    private \PHPUnit\Framework\MockObject\MockObject&JWKSKeyFetcher $jwks_key_fetcher;

    public static function setUpBeforeClass(): void
    {
        self::$rsa_key = openssl_pkey_new(
            [
                'digest_alg'       => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );
    }

    protected function setUp(): void
    {
        $this->jwks_key_fetcher  = $this->createMock(JWKSKeyFetcher::class);
        $this->id_token_verifier = new IDTokenVerifier(new Parser(new JoseEncoder()), $this->generateIssuerValidatorValid(), $this->jwks_key_fetcher, new Sha256(), new Validator());
    }

    public function testItRejectsIDTokenIfPartsAreMissingInTheJWT(): void
    {
        $provider      = $this->createMock(Provider::class);
        $nonce         = 'random_string';
        $fake_id_token = 'aaaaa.aaaaa';

        $this->expectException(MalformedIDTokenException::class);
        $this->id_token_verifier->validate($provider, $nonce, $fake_id_token);
    }

    public function testItRejectsIDTokenIfPayloadCantBeRead(): void
    {
        $provider      = $this->createMock(Provider::class);
        $nonce         = 'random_string';
        $fake_id_token = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.' .
            'fail.' .
            'EkN-DOsnsuRjRO6BxXemmJDm3HbxrbRzXglbN2S4sOkopdU4IsDxTI8jO19W_A4K8ZPJijNLis4EZsHeY559a4DFOd50_OqgHGuERTqY' .
            'ZyuhtF39yxJPAjUESwxk2J5k_4zM3O-vtd1Ghyo4IbqKKSy6J9mTniYJPenn5-HIirE';

        $this->expectException(MalformedIDTokenException::class);
        $this->id_token_verifier->validate($provider, $nonce, $fake_id_token);
    }

    public function testItRejectsIDTokenIfSubjectIdentifierIsNotPresent(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getAuthorizationEndpoint')->willReturn('https://example.com/oauth2/auth');
        $provider->method('getClientId')->willReturn('client_id');
        $nonce = 'random_string';

        $id_token = $this->buildIDToken(
            (new Builder(new JoseEncoder(), ChainedFormatter::default()))->issuedBy('example.com')->permittedFor('client_id')
        );

        $this->expectException(MalformedIDTokenException::class);
        $this->expectExceptionMessage('sub claim is not present or malformed (got NULL)');
        $this->id_token_verifier->validate($provider, $nonce, $id_token);
    }

    public function testItRejectsIDTokenIfAudienceClaimIsInvalid(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getAuthorizationEndpoint')->willReturn('https://example.com/oauth2/auth');
        $provider->method('getClientId')->willReturn('client_id');
        $nonce = 'random_string';

        $id_token = $this->buildIDToken(
            (new Builder(new JoseEncoder(), ChainedFormatter::default()))
                ->withClaim('nonce', $nonce)
                ->issuedBy('example.com')
                ->permittedFor('evil_client_id')
                ->relatedTo('123')
        );

        $this->expectException(MalformedIDTokenException::class);
        $this->expectExceptionMessage('audience claim is not valid');
        $this->id_token_verifier->validate($provider, $nonce, $id_token);
    }

    public function testItRejectsIDTokenIfIssuerIdentifierIsInvalid(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getAuthorizationEndpoint')->willReturn('https://example.com/oauth2/auth');
        $provider->method('getClientId')->willReturn('client_id');
        $nonce = 'random_string';

        $id_token_verifier = new IDTokenVerifier(new Parser(new JoseEncoder()), $this->generateIssuerValidatorInvalid(), $this->jwks_key_fetcher, new Sha256(), new Validator());
        $id_token          = $this->buildIDToken(
            (new Builder(new JoseEncoder(), ChainedFormatter::default()))
                ->withClaim('nonce', $nonce)
                ->issuedBy('evil.example.com')
                ->permittedFor('client_id')
                ->relatedTo('123')
        );

        $this->expectException(MalformedIDTokenException::class);
        $this->expectExceptionMessage('issuer claim is not valid');
        $id_token_verifier->validate($provider, $nonce, $id_token);
    }

    public function testItRejectsIDTokenIfNonceIsInvalid(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getAuthorizationEndpoint')->willReturn('https://example.com/oauth2/auth');
        $provider->method('getClientId')->willReturn('client_id');
        $nonce = 'random_string';

        $id_token = $this->buildIDToken(
            (new Builder(new JoseEncoder(), ChainedFormatter::default()))
                ->withClaim('nonce', 'different_random_string')
                ->issuedBy('evil.example.com')
                ->permittedFor('client_id')
                ->relatedTo('123')
        );

        $this->expectException(MalformedIDTokenException::class);
        $this->expectExceptionMessage('nonce is not valid');
        $this->id_token_verifier->validate($provider, $nonce, $id_token);
    }

    public function testRejectsIDTokenOutsideItsExpectedValidityPeriod(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getAuthorizationEndpoint')->willReturn('https://example.com/oauth2/auth');
        $provider->method('getClientId')->willReturn('client_id');
        $nonce = 'random_string';

        $id_token = $this->buildIDToken(
            (new Builder(new JoseEncoder(), ChainedFormatter::default()))
                ->withClaim('nonce', $nonce)
                ->issuedBy('evil.example.com')
                ->permittedFor('client_id')
                ->relatedTo('123')
                ->issuedAt(new \DateTimeImmutable('+1 hour'))
        );

        $this->expectException(MalformedIDTokenException::class);
        $this->expectExceptionMessage('the token is outside its validity period');
        $this->id_token_verifier->validate($provider, $nonce, $id_token);
    }

    public function testItRejectsIDTokenWithIncorrectSignature(): void
    {
        $provider = $this->createMock(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $provider->method('getAuthorizationEndpoint')->willReturn('https://example.com/oauth2/auth');
        $provider->method('getClientId')->willReturn('client_id_2');
        $nonce = 'random_string';

        $id_token_builder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $id_token         = $id_token_builder
            ->issuedBy('example.com')
            ->withClaim('nonce', $nonce)
            ->permittedFor('client_id_2')
            ->relatedTo('123')
            ->getToken(new \Lcobucci\JWT\Signer\Hmac\Sha256(), InMemory::plainText(str_repeat('a', 32)))->toString();

        $key_details = openssl_pkey_get_details(self::$rsa_key);
        $this->jwks_key_fetcher->method('fetchKey')->willReturn([$key_details['key']]);

        $this->expectException(MalformedIDTokenException::class);
        $this->expectExceptionMessage('ID token signature is not valid');
        $this->id_token_verifier->validate($provider, $nonce, $id_token);
    }

    /**
     * @dataProvider dataProviderValidIDToken
     */
    public function testItAcceptsAValidIDToken(bool $with_jwks_key): void
    {
        $provider = $this->createMock(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $provider->method('getAuthorizationEndpoint')->willReturn('https://example.com/oauth2/auth');
        $provider->method('getClientId')->willReturn('client_id_2');
        $nonce = 'random_string';

        $id_token = $this->buildIDToken(
            (new Builder(new JoseEncoder(), ChainedFormatter::default()))
                ->withClaim('nonce', $nonce)
                ->issuedBy('example.com')
                ->permittedFor('client_id_2')
                ->relatedTo('123')
        );
        if ($with_jwks_key) {
            $key_details = openssl_pkey_get_details(self::$rsa_key);
            $this->jwks_key_fetcher->method('fetchKey')->willReturn([$key_details['key']]);
        } else {
            $this->jwks_key_fetcher->method('fetchKey')->willReturn(null);
        }

        $verified_sub = $this->id_token_verifier->validate($provider, $nonce, $id_token);
        $this->assertSame('123', $verified_sub);
    }

    public static function dataProviderValidIDToken(): array
    {
        return [
            [false],
            [true],
        ];
    }

    private function buildIDToken(Builder $id_token_builder): string
    {
        openssl_pkey_export(self::$rsa_key, $private_key);
        return $id_token_builder->getToken(new Sha256(), InMemory::plainText($private_key))->toString();
    }

    private function generateIssuerValidatorValid(): IssuerClaimValidator
    {
        return new class implements IssuerClaimValidator
        {
            public function isIssuerClaimValid(Provider $provider, string $iss_from_id_token): bool
            {
                return true;
            }
        };
    }

    private function generateIssuerValidatorInvalid(): IssuerClaimValidator
    {
        return new class implements IssuerClaimValidator
        {
            public function isIssuerClaimValid(Provider $provider, string $iss_from_id_token): bool
            {
                return false;
            }
        };
    }
}
