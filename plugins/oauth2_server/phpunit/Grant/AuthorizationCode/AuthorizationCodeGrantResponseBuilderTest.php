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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenCreator;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenWithIdentifier;
use Tuleap\User\OAuth2\Scope\DemoOAuth2Scope;

final class AuthorizationCodeGrantResponseBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGeneratesSuccessfulRequestRepresentation(): void
    {
        $access_token_creator = \Mockery::mock(OAuth2AccessTokenCreator::class);
        $builder              = new AuthorizationCodeGrantResponseBuilder($access_token_creator);

        $access_token_creator->shouldReceive('issueAccessToken')->andReturn(
            new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20'))
        );

        $representation = $builder->buildResponse(
            new \DateTimeImmutable('@10'),
            OAuth2AuthorizationCode::approveForSetOfScopes(
                new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
                new \PFUser(['language_id' => 'en']),
                [DemoOAuth2Scope::fromItself()]
            )
        );

        $this->assertEquals($representation->access_token, 'identifier');
    }
}
