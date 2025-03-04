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

namespace Tuleap\OAuth2ServerCore\Grant;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenCreator;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenWithIdentifier;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCode;
use Tuleap\OAuth2ServerCore\OAuth2TestScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\IDToken\OpenIDConnectIDTokenCreator;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2OfflineAccessScope;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshToken;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshTokenCreator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AccessTokenGrantRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AccessTokenCreator
     */
    private $access_token_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2RefreshTokenCreator
     */
    private $refresh_token_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OpenIDConnectIDTokenCreator
     */
    private $id_token_creator;
    /**
     * @var AccessTokenGrantRepresentationBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->access_token_creator  = $this->createMock(OAuth2AccessTokenCreator::class);
        $this->refresh_token_creator = $this->createMock(OAuth2RefreshTokenCreator::class);
        $this->id_token_creator      = $this->createMock(OpenIDConnectIDTokenCreator::class);

        $this->builder = new AccessTokenGrantRepresentationBuilder(
            $this->access_token_creator,
            $this->refresh_token_creator,
            $this->id_token_creator
        );
    }

    public function testGeneratesSuccessfulRequestRepresentationFromAuthorizationCode(): void
    {
        $this->access_token_creator->method('issueAccessToken')->willReturn(
            new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20'))
        );
        $this->refresh_token_creator->method('issueRefreshTokenIdentifierFromAuthorizationCode')->willReturn(new ConcealedString('rt_token'));
        $this->id_token_creator->method('issueIDTokenFromAuthorizationCode')->willReturn('jwt_id_token');

        $representation = $this->builder->buildRepresentationFromAuthorizationCode(
            new \DateTimeImmutable('@10'),
            new OAuth2App(1, 'Name', 'https://example.com', true, new \Project(['group_id' => 102])),
            OAuth2AuthorizationCode::approveForSetOfScopes(
                new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
                new \PFUser(['language_id' => 'en']),
                'pkce_code_challenge',
                'oidc_nonce',
                [OAuth2TestScope::fromItself()]
            )
        );

        $this->assertNotEmpty(json_encode($representation, JSON_THROW_ON_ERROR));
    }

    public function testGeneratesSuccessfulRequestRepresentationFromRefreshToken(): void
    {
        $this->access_token_creator->method('issueAccessToken')->willReturn(
            new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20'))
        );
        $this->refresh_token_creator->method('issueRefreshTokenIdentifierFromExistingRefreshToken')->willReturn(new ConcealedString('rt_token'));
        $this->id_token_creator->expects(self::never())->method('issueIDTokenFromAuthorizationCode');

        $representation = $this->builder->buildRepresentationFromRefreshToken(
            new \DateTimeImmutable('@10'),
            OAuth2RefreshToken::createWithASetOfScopes(
                1,
                [OAuth2TestScope::fromItself(), OAuth2OfflineAccessScope::fromItself()]
            )
        );

        $this->assertNotEmpty(json_encode($representation, JSON_THROW_ON_ERROR));
    }
}
