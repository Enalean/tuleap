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

namespace Tuleap\OAuth2Server\Grant;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenWithIdentifier;

/**
 * @psalm-immutable
 *
 * @see https://tools.ietf.org/html/rfc6749#section-5.1
 */
final class OAuth2AccessTokenSuccessfulRequestRepresentation implements \JsonSerializable
{
    /**
     * @var ConcealedString
     */
    private $access_token;
    /**
     * @var ConcealedString|null
     */
    private $refresh_token;
    /**
     * @var int
     */
    private $expires_in;
    /**
     * @var string|null
     */
    private $id_token;

    private function __construct(
        ConcealedString $access_token_identifier,
        ?ConcealedString $refresh_token,
        int $expires_in_seconds,
        ?string $id_token
    ) {
        $this->access_token  = $access_token_identifier;
        $this->refresh_token = $refresh_token;
        if ($expires_in_seconds <= 0) {
            throw new CannotSetANegativeExpirationDelayOnAccessTokenException($expires_in_seconds);
        }
        $this->expires_in = $expires_in_seconds;
        $this->id_token   = $id_token;
    }

    public static function fromAccessTokenAndRefreshToken(
        OAuth2AccessTokenWithIdentifier $access_token,
        ?ConcealedString $refresh_token,
        \DateTimeImmutable $current_time
    ): self {
        return new self(
            $access_token->getIdentifier(),
            $refresh_token,
            $access_token->getExpiration()->getTimestamp() - $current_time->getTimestamp(),
            null
        );
    }

    public static function fromAccessTokenAndRefreshTokenWithUserAuthentication(
        OAuth2AccessTokenWithIdentifier $access_token,
        ?ConcealedString $refresh_token,
        ?string $id_token,
        \DateTimeImmutable $current_time
    ): self {
        return new self(
            $access_token->getIdentifier(),
            $refresh_token,
            $access_token->getExpiration()->getTimestamp() - $current_time->getTimestamp(),
            $id_token
        );
    }

    public function jsonSerialize(): array
    {
        $json_encoded = [
            'token_type'   => 'bearer',
            'access_token' => $this->access_token->getString(),
            'expires_in'   => $this->expires_in,
        ];
        if ($this->refresh_token !== null) {
            $json_encoded['refresh_token'] = $this->refresh_token->getString();
        }
        if ($this->id_token !== null) {
            $json_encoded['id_token'] = $this->id_token;
        }

        return $json_encoded;
    }
}
