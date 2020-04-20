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

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tuleap\JWT\JWKS\PKCS1Format;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class JWKSKeyFetcher
{
    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;

    public function __construct(ClientInterface $client, RequestFactoryInterface $request_factory)
    {
        $this->client          = $client;
        $this->request_factory = $request_factory;
    }

    /**
     * @return string[]|null
     *
     * @psalm-return non-empty-list<string>|null
     */
    public function fetchKey(Provider $provider): ?array
    {
        $jwks_endpoint = $provider->getJWKSEndpoint();
        if ($jwks_endpoint === null) {
            return null;
        }

        $response = $this->client->sendRequest($this->request_factory->createRequest('GET', $jwks_endpoint));
        if ($response->getStatusCode() !== 200) {
            throw new JWKSKeyFetcherException(
                sprintf(
                    'JWKS endpoint (%s) responded with a non expected status code : %d %s',
                    $jwks_endpoint,
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                )
            );
        }

        $jwks_document = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        if (! isset($jwks_document['keys']) || ! is_array($jwks_document['keys'])) {
            throw new JWKSKeyFetcherException('JWKS document without or a malformed `keys` attribute');
        }

        $rsa_keys = [];

        foreach ($jwks_document['keys'] as $key) {
            if (! isset($key['kty']) || $key['kty'] !== 'RSA') {
                continue;
            }

            if (! isset($key['n'], $key['e']) || ! is_string($key['n']) || ! is_string($key['e'])) {
                throw new JWKSKeyFetcherException('RS256 key without or malformed modulus or exponent');
            }

            try {
                $n = sodium_base642bin($key['n'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
                $e = sodium_base642bin($key['e'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
            } catch (\SodiumException $exception) {
                throw new JWKSKeyFetcherException('Modulus or exponent is not Base64 URL safe no padding encoded');
            }

            $rsa_keys[] = PKCS1Format::convertFromRSAModulusAndExponent($n, $e);
        }

        if (empty($rsa_keys)) {
            throw new JWKSKeyFetcherException('Mandatory RSA key is missing in the JWKS document');
        }

        return $rsa_keys;
    }
}
