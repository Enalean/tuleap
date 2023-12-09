<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2ServerCore\OpenIDConnect;

use Lcobucci\JWT\Signer;
use Tuleap\OAuth2ServerCore\App\ClientIdentifier;
use Tuleap\OAuth2ServerCore\App\OAuth2App;

final class OpenIDConnectTokenBuilder
{
    // See https://tools.ietf.org/html/rfc7515#section-4.1.4
    private const HEADER_KEY_ID = 'kid';

    public function __construct(
        private JWTBuilderFactory $builder_factory,
        private OpenIDConnectSigningKeyFactory $signing_key_factory,
        private \DateInterval $token_expiration_delay,
        private Signer $signer,
    ) {
    }

    /**
     * @param array<non-empty-string,mixed> $additional_claims
     */
    public function getToken(\DateTimeImmutable $current_time, OAuth2App $app, \PFUser $user, array $additional_claims): string
    {
        $signing_private_key = $this->signing_key_factory->getKey($current_time);

        $builder = $this->builder_factory->getBuilder();

        foreach ($additional_claims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        $builder = $builder->issuedBy(Issuer::toString())
            ->relatedTo((string) $user->getId())
            ->permittedFor(ClientIdentifier::fromOAuth2App($app)->toString())
            ->issuedAt($current_time)
            ->expiresAt($current_time->add($this->token_expiration_delay))
            ->withHeader(self::HEADER_KEY_ID, $signing_private_key->getFingerprintPublicKey());

        return $builder->getToken($this->signer, $signing_private_key->getPrivateKey())->toString();
    }
}
