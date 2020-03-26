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

use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenWithIdentifier;

final class OAuth2AccessTokenSuccessfulRequestRepresentationTest extends TestCase
{
    public function testBuildsSuccessfulRequestRepresentationThatCanBeJSONEncoded(): void
    {
        $representation = OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
            new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
            null,
            new \DateTimeImmutable('@10')
        );

        $this->assertJsonStringEqualsJsonString(
            '{"access_token":"identifier","token_type":"bearer","expires_in":10}',
            json_encode($representation, JSON_THROW_ON_ERROR)
        );
    }

    public function testBuildsSuccessfulRequestRepresentationIncludingARefreshTokenThatCanBeJSONEncoded(): void
    {
        $representation = OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
            new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
            new ConcealedString('refresh_identifier'),
            new \DateTimeImmutable('@10')
        );

        $this->assertJsonStringEqualsJsonString(
            '{"access_token":"identifier","refresh_token":"refresh_identifier","token_type":"bearer","expires_in":10}',
            json_encode($representation, JSON_THROW_ON_ERROR)
        );
    }

    public function testDoesNotBuildRequestRepresentationWhenTheAccessTokenHaveAlreadyExpired(): void
    {
        $this->expectException(CannotSetANegativeExpirationDelayOnAccessTokenException::class);
        OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
            new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@10')),
            null,
            new \DateTimeImmutable('@20')
        );
    }

    public function testDoesNotBuildRequestRepresentationWhenTheAccessTokenExpiresNow(): void
    {
        $current_time = new \DateTimeImmutable('@10');
        $this->expectException(CannotSetANegativeExpirationDelayOnAccessTokenException::class);
        OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
            new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), $current_time),
            null,
            $current_time
        );
    }
}
