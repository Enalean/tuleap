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

namespace Tuleap\OAuth2Server\OpenIDConnect\JWK;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\OAuth2Server\OpenIDConnect\IDToken\OpenIDConnectSigningKeyFactory;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class JWKSDocumentEndpointController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var OpenIDConnectSigningKeyFactory
     */
    private $signing_key_factory;
    /**
     * @var \DateInterval
     */
    private $max_cache_age;
    /**
     * @var JSONResponseBuilder
     */
    private $json_response_builder;

    public function __construct(
        OpenIDConnectSigningKeyFactory $signing_key_factory,
        \DateInterval $max_cache_age,
        JSONResponseBuilder $json_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->signing_key_factory   = $signing_key_factory;
        $this->max_cache_age         = $max_cache_age;
        $this->json_response_builder = $json_response_builder;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $keys = [];
        foreach ($this->signing_key_factory->getPublicKeys(new \DateTimeImmutable()) as $public_key) {
            $keys[] = JSONWebKey::fromSigningPublicKey($public_key);
        }

        $jwks = new JSONWebKeySet(...$keys);

        return $this->json_response_builder->fromData($jwks)
            ->withHeader('Cache-Control', sprintf('max-age=%d,public', $this->getMaxAgeInSeconds()));
    }

    private function getMaxAgeInSeconds(): int
    {
        $date = new \DateTimeImmutable();

        return $date->add($this->max_cache_age)->getTimestamp() - $date->getTimestamp();
    }
}
