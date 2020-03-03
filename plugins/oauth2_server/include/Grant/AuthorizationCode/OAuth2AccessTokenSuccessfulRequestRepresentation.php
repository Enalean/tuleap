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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenWithIdentifier;

/**
 * @psalm-immutable
 *
 * @see https://tools.ietf.org/html/rfc6749#section-5.1
 */
final class OAuth2AccessTokenSuccessfulRequestRepresentation
{
    /**
     * @var string
     */
    public $access_token;
    /**
     * @var string
     */
    public $token_type = 'bearer';
    /**
     * @var int
     */
    public $expires_in;

    private function __construct(ConcealedString $access_token_identifier, int $expires_in_seconds)
    {
        $this->access_token = $access_token_identifier->getString();
        if ($expires_in_seconds <= 0) {
            throw new CannotSetANegativeExpirationDelayOnAccessTokenException($expires_in_seconds);
        }
        $this->expires_in = $expires_in_seconds;
    }

    public static function fromAccessToken(OAuth2AccessTokenWithIdentifier $access_token, \DateTimeImmutable $current_time): self
    {
        return new self(
            $access_token->getIdentifier(),
            $access_token->getExpiration()->getTimestamp() - $current_time->getTimestamp()
        );
    }
}
