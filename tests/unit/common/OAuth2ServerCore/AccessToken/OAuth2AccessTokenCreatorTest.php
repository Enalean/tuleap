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

namespace Tuleap\OAuth2ServerCore\AccessToken;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeSaver;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2AccessTokenCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const int EXPECTED_EXPIRATION_DELAY_SECONDS = 30;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AccessTokenDAO
     */
    private OAuth2AccessTokenDAO&MockObject $access_token_dao;
    private OAuth2ScopeSaver&MockObject $scope_saver;

    private OAuth2AccessTokenCreator $token_creator;

    #[\Override]
    protected function setUp(): void
    {
        $this->access_token_dao = $this->createMock(OAuth2AccessTokenDAO::class);
        $this->scope_saver      = $this->createMock(OAuth2ScopeSaver::class);

        $formatter = new class implements SplitTokenFormatter
        {
            #[\Override]
            public function getIdentifier(SplitToken $token): ConcealedString
            {
                return $token->getVerificationString()->getString();
            }
        };

        $this->token_creator = new OAuth2AccessTokenCreator(
            $formatter,
            new SplitTokenVerificationStringHasher(),
            $this->access_token_dao,
            $this->scope_saver,
            new \DateInterval('PT' . self::EXPECTED_EXPIRATION_DELAY_SECONDS . 'S'),
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testCanIssueANewAccessToken(): void
    {
        $current_time              = new \DateTimeImmutable('@10');
        $authorization_grant_id    = 3;
        $generated_access_token_id = 1;

        $this->access_token_dao->expects($this->once())->method('create')->willReturn($generated_access_token_id);
        $this->scope_saver->expects($this->once())->method('saveScopes');

        /** @var non-empty-array<AuthenticationScope<OAuth2ScopeIdentifier>> $scopes */
        $scopes = [$this->createStub(AuthenticationScope::class)];

        $access_token = $this->token_creator->issueAccessToken(
            $current_time,
            $authorization_grant_id,
            $scopes,
        );

        $this->assertEquals(
            $current_time->getTimestamp() + self::EXPECTED_EXPIRATION_DELAY_SECONDS,
            $access_token->getExpiration()->getTimestamp()
        );
    }

    public function testIssueNewAccessTokenIdentifierEachTime(): void
    {
        $current_time = new \DateTimeImmutable('@10');

        $this->access_token_dao->expects($this->exactly(2))->method('create')->willReturn(1);
        $this->scope_saver->expects($this->exactly(2))->method('saveScopes');
        /** @var non-empty-array<AuthenticationScope<OAuth2ScopeIdentifier>> $scopes */
        $scopes = [$this->createStub(AuthenticationScope::class)];

        $access_token_1 = $this->token_creator->issueAccessToken($current_time, 1, $scopes);
        $access_token_2 = $this->token_creator->issueAccessToken($current_time, 2, $scopes);

        $this->assertNotEquals(
            $access_token_1->getIdentifier()->getString(),
            $access_token_2->getIdentifier()->getString()
        );
    }
}
