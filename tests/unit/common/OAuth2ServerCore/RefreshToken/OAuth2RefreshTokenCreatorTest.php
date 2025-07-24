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

namespace Tuleap\OAuth2ServerCore\RefreshToken;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCode;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeSaver;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2RefreshTokenCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const EXPECTED_EXPIRATION_DELAY_SECONDS = 30;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2RefreshTokenDAO
     */
    private $refresh_token_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2ScopeSaver
     */
    private $scope_saver;
    /**
     * @var OAuth2RefreshTokenCreator
     */
    private $refresh_token_creator;

    #[\Override]
    protected function setUp(): void
    {
        $this->refresh_token_dao = $this->createMock(OAuth2RefreshTokenDAO::class);
        $this->scope_saver       = $this->createMock(OAuth2ScopeSaver::class);

        $formatter = new class implements SplitTokenFormatter
        {
            #[\Override]
            public function getIdentifier(SplitToken $token): ConcealedString
            {
                return $token->getVerificationString()->getString();
            }
        };

        $this->refresh_token_creator = new OAuth2RefreshTokenCreator(
            OAuth2OfflineAccessScope::fromItself(),
            $formatter,
            new SplitTokenVerificationStringHasher(),
            $this->refresh_token_dao,
            $this->scope_saver,
            new \DateInterval('PT' . self::EXPECTED_EXPIRATION_DELAY_SECONDS . 'S'),
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testCanIssueANewRefreshTokenFromAuthorizationCode(): void
    {
        $current_time = new \DateTimeImmutable('@10');
        $auth_code    = $this->getAuthorizationCode([OAuth2OfflineAccessScope::fromItself()]);

        $this->refresh_token_dao->expects($this->once())->method('create')
            ->with($auth_code->getID(), self::isString(), $current_time->getTimestamp() + self::EXPECTED_EXPIRATION_DELAY_SECONDS)
            ->willReturn(2);
        $this->scope_saver->expects($this->once())->method('saveScopes');

        $refresh_token = $this->refresh_token_creator->issueRefreshTokenIdentifierFromAuthorizationCode(
            $current_time,
            $auth_code
        );

        $this->assertNotNull($refresh_token);
    }

    public function testCanIssueANewRefreshTokenIdentifierFromRefreshToken(): void
    {
        $current_time  = new \DateTimeImmutable('@10');
        $refresh_token = OAuth2RefreshToken::createWithASetOfScopes(12, [OAuth2OfflineAccessScope::fromItself()]);

        $this->refresh_token_dao->expects($this->once())->method('create')
            ->with($refresh_token->getAssociatedAuthorizationCodeID(), self::isString(), $current_time->getTimestamp() + self::EXPECTED_EXPIRATION_DELAY_SECONDS)
            ->willReturn(3);
        $this->scope_saver->expects($this->once())->method('saveScopes');

        $new_refresh_token_identifier = $this->refresh_token_creator->issueRefreshTokenIdentifierFromExistingRefreshToken(
            $current_time,
            $refresh_token
        );

        $this->assertNotNull($new_refresh_token_identifier);
    }

    public function testIssueNewRefreshTokenIdentifierEachTime(): void
    {
        $current_time = new \DateTimeImmutable('@10');

        $this->refresh_token_dao->method('create')->willReturn(1);
        $this->scope_saver->method('saveScopes');

        $refresh_token_1 = $this->refresh_token_creator->issueRefreshTokenIdentifierFromAuthorizationCode($current_time, $this->getAuthorizationCode([OAuth2OfflineAccessScope::fromItself()]));
        $refresh_token_2 = $this->refresh_token_creator->issueRefreshTokenIdentifierFromAuthorizationCode($current_time, $this->getAuthorizationCode([OAuth2OfflineAccessScope::fromItself()]));

        self::assertNotNull($refresh_token_1);
        self::assertNotNull($refresh_token_2);
        self::assertFalse($refresh_token_1->isIdenticalTo($refresh_token_2));
    }

    public function testDoesNotIssueRefreshTokenWhenAuthorizationCodeDoesNotHaveOfflineScope(): void
    {
        $current_time = new \DateTimeImmutable('@10');
        $scope        = $this->createMock(AuthenticationScope::class);
        $scope->method('getIdentifier')->willReturn(OAuth2ScopeIdentifier::fromIdentifierKey('notoffline'));
        $auth_code = $this->getAuthorizationCode([$scope]);

        $refresh_token = $this->refresh_token_creator->issueRefreshTokenIdentifierFromAuthorizationCode(
            $current_time,
            $auth_code
        );

        $this->assertNull($refresh_token);
    }

    private function getAuthorizationCode(array $scopes): OAuth2AuthorizationCode
    {
        return OAuth2AuthorizationCode::approveForSetOfScopes(
            new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
            UserTestBuilder::aUser()->build(),
            null,
            null,
            $scopes
        );
    }
}
