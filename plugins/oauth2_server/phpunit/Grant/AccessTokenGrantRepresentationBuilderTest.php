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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenCreator;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenWithIdentifier;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCode;
use Tuleap\OAuth2Server\OAuth2TestScope;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenCreator;

final class AccessTokenGrantRepresentationBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGeneratesSuccessfulRequestRepresentation(): void
    {
        $access_token_creator  = \Mockery::mock(OAuth2AccessTokenCreator::class);
        $refresh_token_creator = \Mockery::mock(OAuth2RefreshTokenCreator::class);
        $builder               = new AccessTokenGrantRepresentationBuilder($access_token_creator, $refresh_token_creator);

        $access_token_creator->shouldReceive('issueAccessToken')->andReturn(
            new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20'))
        );
        $refresh_token_creator->shouldReceive('issueRefreshTokenIdentifierFromAuthorizationCode')->andReturn(new ConcealedString('rt_token'));

        $representation = $builder->buildRepresentationFromAuthorizationCode(
            new \DateTimeImmutable('@10'),
            OAuth2AuthorizationCode::approveForSetOfScopes(
                new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
                new \PFUser(['language_id' => 'en']),
                'pkce_code_challenge',
                [OAuth2TestScope::fromItself()]
            )
        );

        $this->assertNotEmpty(json_encode($representation, JSON_THROW_ON_ERROR));
    }
}
