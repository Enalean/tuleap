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

namespace Tuleap\OAuth2ServerCore\Grant\AuthorizationCode;

use Project;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeSaver;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2AuthorizationCodeCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const EXPECTED_EXPIRATION_DELAY_SECONDS = 30;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AuthorizationCodeDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2ScopeSaver
     */
    private $scope_saver;
    /**
     * @var OAuth2AuthorizationCodeCreator
     */
    private $auth_code_creator;


    protected function setUp(): void
    {
        $this->dao         = $this->createMock(OAuth2AuthorizationCodeDAO::class);
        $this->scope_saver = $this->createMock(OAuth2ScopeSaver::class);

        $formatter = new class implements SplitTokenFormatter
        {
            public function getIdentifier(SplitToken $token): ConcealedString
            {
                return $token->getVerificationString()->getString();
            }
        };

        $this->auth_code_creator = new OAuth2AuthorizationCodeCreator(
            $formatter,
            new SplitTokenVerificationStringHasher(),
            $this->dao,
            $this->scope_saver,
            new \DateInterval('PT' . self::EXPECTED_EXPIRATION_DELAY_SECONDS . 'S'),
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testCanCreateAnAuthorizationCode(): void
    {
        $current_time = new \DateTimeImmutable('@10');

        $this->dao->expects(self::once())->method('create')
            ->with(12, 102, self::anything(), $current_time->getTimestamp() + self::EXPECTED_EXPIRATION_DELAY_SECONDS, 'pkce_code_chall', 'oidc_nonce')
            ->willReturn(1);
        $this->scope_saver->expects(self::once())->method('saveScopes');

        $auth_code = $this->auth_code_creator->createAuthorizationCodeIdentifier(
            $current_time,
            $this->buildOAuth2App(),
            [$this->createMock(AuthenticationScope::class)],
            new \PFUser(['language_id' => 'en', 'user_id' => '102']),
            'pkce_code_chall',
            'oidc_nonce'
        );

        $this->assertNotEmpty($auth_code->getString());
    }

    public function testAuthCodeIdentifierIsDifferentEachTimeAuAuthorizationCodeIsCreated(): void
    {
        $current_time = new \DateTimeImmutable('@10');
        $auth_scopes  = [$this->createMock(AuthenticationScope::class)];

        $this->dao->method('create')->willReturn(2, 3);
        $this->scope_saver->method('saveScopes');

        $auth_code_1 = $this->auth_code_creator->createAuthorizationCodeIdentifier(
            $current_time,
            $this->buildOAuth2App(),
            $auth_scopes,
            new \PFUser(['language_id' => 'en', 'user_id' => '102']),
            'pkce_code_chall',
            'oidc_nonce'
        );
        $auth_code_2 = $this->auth_code_creator->createAuthorizationCodeIdentifier(
            $current_time,
            $this->buildOAuth2App(),
            $auth_scopes,
            new \PFUser(['language_id' => 'en', 'user_id' => '103']),
            'pkce_code_chall',
            'oidc_nonce'
        );

        $this->assertFalse($auth_code_1->isIdenticalTo($auth_code_2));
    }

    private function buildOAuth2App(): OAuth2App
    {
        return new OAuth2App(12, 'Name', 'https://example.com', true, new Project(['group_id' => 102]));
    }
}
