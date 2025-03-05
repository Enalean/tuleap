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

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2RefreshTokenRevokerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var OAuth2RefreshTokenRevoker */
    private $revoker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenIdentifierTranslator
     */
    private $refresh_token_unserializer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AuthorizationCodeRevoker
     */
    private $authorization_code_revoker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2RefreshTokenDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenVerificationStringHasher
     */
    private $hasher;

    protected function setUp(): void
    {
        $this->refresh_token_unserializer = $this->createMock(SplitTokenIdentifierTranslator::class);
        $this->authorization_code_revoker = $this->createMock(OAuth2AuthorizationCodeRevoker::class);
        $this->dao                        = $this->createMock(OAuth2RefreshTokenDAO::class);
        $this->hasher                     = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->revoker                    = new OAuth2RefreshTokenRevoker(
            $this->refresh_token_unserializer,
            $this->authorization_code_revoker,
            $this->dao,
            $this->hasher,
        );
    }

    public function testItThrowsWhenTheRefreshTokenIsNotAssociatedToTheApp(): void
    {
        $this->refresh_token_unserializer->expects(self::once())->method('getSplitToken')
            ->willReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->expects(self::once())->method('searchRefreshTokenByApp')
            ->willReturn(null);

        $this->expectException(OAuth2RefreshTokenNotFoundException::class);
        $this->revoker->revokeGrantOfRefreshToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    public function testItThrowsWhenTheRefreshTokenIsInvalid(): void
    {
        $this->refresh_token_unserializer->expects(self::once())->method('getSplitToken')
            ->willReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->expects(self::once())->method('searchRefreshTokenByApp')
            ->willReturn(['authorization_code_id' => 89, 'verifier' => 'valid_verifier']);
        $this->hasher->expects(self::once())->method('verifyHash')->willReturn(false);

        $this->expectException(InvalidOAuth2RefreshTokenException::class);
        $this->revoker->revokeGrantOfRefreshToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    public function testRevokeGrantOfRefreshToken(): void
    {
        $this->refresh_token_unserializer->expects(self::once())->method('getSplitToken')
            ->willReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->expects(self::once())->method('searchRefreshTokenByApp')
            ->willReturn(['authorization_code_id' => 89, 'verifier' => 'valid_verifier']);
        $this->hasher->expects(self::once())->method('verifyHash')->willReturn(true);
        $this->authorization_code_revoker->expects(self::once())->method('revokeByAuthCodeId')
            ->with(89);

        $this->revoker->revokeGrantOfRefreshToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    private function buildApp(): OAuth2App
    {
        return new OAuth2App(12, 'Client', 'https://example.com', false, new \Project(['group_id' => 101]));
    }
}
