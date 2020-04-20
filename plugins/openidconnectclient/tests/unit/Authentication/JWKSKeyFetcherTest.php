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

namespace Tuleap\OpenIDConnectClient\Authentication;

use Http\Mock\Client;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OpenIDConnectClient\Provider\Provider;

final class JWKSKeyFetcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Client
     */
    private $client;
    /**
     * @var JWKSKeyFetcher
     */
    private $key_fetcher;

    protected function setUp(): void
    {
        $this->client      = new Client();
        $this->key_fetcher = new JWKSKeyFetcher($this->client, HTTPFactoryBuilder::requestFactory());
    }

    public function testDoesNotTryToFetchTheKeyIfTheProviderDoesNotHaveAJWKSEndpoint(): void
    {
        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('getJWKSEndpoint')->andReturn(null);

        $this->assertNull($this->key_fetcher->fetchKey($provider));
    }

    public function testFetchKeyFromValidJWKSDocument(): void
    {
        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('getJWKSEndpoint')->andReturn('https://example.com/oidc/jwks');

        $this->client->addResponse(
            HTTPFactoryBuilder::responseFactory()->createResponse(200)->withBody(
                HTTPFactoryBuilder::streamFactory()->createStream(
                    '{"keys":[{"kty":"RSA","alg":"RS256","use":"sig","kid":"id","n":"1nenAjPEkIj2FL2dAq0SP6ZT2bsnCJZvwaOKimvwW0CZzijLV8iCcH20IdHvNvQsydyj802GoT07kSQOs8gqecgQsPPHhI56U8qaGMcPjtx7TTVhFTD0X1MSf7pJRyXgmXRfZYsernHcrK4PZDXNHhRtqXZHUAzH10MELhglVOPkUHRD0noIc-OFSTJcXIFD-fPTJcrug9P4cxdc6XO59mWssx-nqZL1n4uX0Z6v6SC2FLFoOIBgxuKwF8ms_hTPEPY7PmBm4QUGOi0uQBQ2ciq-nbx3dnJ-kr5SmhKB90M3lIJrza3Wbhgv6xJvvOOjeiNzd1ctcZBHdk1uHOQFPjje-XtwSxbDBBgtwfmTfZj_qtLo5n-Zq38cTOjoJNCE3s0Nj84aWJZFE-JLcR7KM3E3WxIx_rVKzc7nhaVInBvsgGlSlt79JVXIjnBjaZ_6p5xQykL-8r5RrIHbet8Th-SRxjR6YaJOXa3CD7YC9X1W_Ho_8asPM7hQmbM_o1iSP_JitW7ZexGP1OMJqP0Idp7a4DgscvEI9Ol4Vi1RGbqRCSpZRDk_V2OBAOgl1C_hS7VJ-NgSVtiU1E6qV0n2Lstv04shqTjcKnWqzUIzNCUqB-isvmZoS7O3gcWHBHjdMXE6s3vjFQn2dwlgMxsPt68dPwVnWCKMY5tI3apnly0","e":"AQAB"}]}'
                )
            )
        );

        $keys_pem_format = $this->key_fetcher->fetchKey($provider);

        $this->assertNotEmpty($keys_pem_format);
    }

    public function testCannotFetchKeyWhenTheJWKSEndpointDoesNotHaveTheExpectedHTTPCodeStatus(): void
    {
        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('getJWKSEndpoint')->andReturn('https://example.com/oidc/jwks');

        $this->client->addResponse(HTTPFactoryBuilder::responseFactory()->createResponse(500));

        $this->expectException(JWKSKeyFetcherException::class);
        $this->key_fetcher->fetchKey($provider);
    }

    /**
     * @dataProvider dataProviderInvalidJWKSDocument
     */
    public function testCannotFetchKeyFromAnInvalidJWKSDocument(string $document): void
    {
        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('getJWKSEndpoint')->andReturn('https://example.com/oidc/jwks');

        $this->client->addResponse(
            HTTPFactoryBuilder::responseFactory()->createResponse(200)->withBody(
                HTTPFactoryBuilder::streamFactory()->createStream(
                    $document
                )
            )
        );

        $this->expectException(JWKSKeyFetcherException::class);
        $this->key_fetcher->fetchKey($provider);
    }

    public function dataProviderInvalidJWKSDocument(): array
    {
        return [
            'No keys attribute'           => ['{}'],
            'No keys'                     => ['{"keys":[]}'],
            'Invalid keys'                => ['{"keys":"wrong keys format"}'],
            'No RSA key'                  => ['{"keys":[{"kty":"ES256"}]}'],
            'RSA without n'               => ['{"keys":[{"kty":"RSA", "e": "A"}]}'],
            'RSA without e'               => ['{"keys":[{"kty":"RSA", "n": "A"}]}'],
            'Incorrectly encoded n and e' => ['{"keys":[{"kty":"RSA", "n": "Z", "e": "Z"}]}'],
        ];
    }
}
