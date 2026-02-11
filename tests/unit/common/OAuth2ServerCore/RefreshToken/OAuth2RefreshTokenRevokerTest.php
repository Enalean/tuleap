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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
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
    private OAuth2RefreshTokenRevoker $revoker;
    private SplitTokenIdentifierTranslator&MockObject $refresh_token_unserializer;
    private OAuth2AuthorizationCodeRevoker&MockObject $authorization_code_revoker;
    private OAuth2RefreshTokenDAO&MockObject $dao;
    private SplitTokenVerificationStringHasher&Stub $hasher;

    #[\Override]
    protected function setUp(): void
    {
        $this->refresh_token_unserializer = $this->createMock(SplitTokenIdentifierTranslator::class);
        $this->authorization_code_revoker = $this->createMock(OAuth2AuthorizationCodeRevoker::class);
        $this->dao                        = $this->createMock(OAuth2RefreshTokenDAO::class);
        $this->hasher                     = $this->createStub(SplitTokenVerificationStringHasher::class);
        $this->revoker                    = new OAuth2RefreshTokenRevoker(
            $this->refresh_token_unserializer,
            $this->authorization_code_revoker,
            $this->dao,
            $this->hasher,
        );
    }

    public function testItThrowsWhenTheRefreshTokenIsNotAssociatedToTheApp(): void
    {
        $this->refresh_token_unserializer->expects($this->once())->method('getSplitToken')
            ->willReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->expects($this->once())->method('searchRefreshTokenByApp')
            ->willReturn(null);
        $this->authorization_code_revoker->expects($this->never())->method('revokeByAuthCodeId');

        $this->expectException(OAuth2RefreshTokenNotFoundException::class);
        $this->revoker->revokeGrantOfRefreshToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    public function testItThrowsWhenTheRefreshTokenIsInvalid(): void
    {
        $this->refresh_token_unserializer->expects($this->once())->method('getSplitToken')
            ->willReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->expects($this->once())->method('searchRefreshTokenByApp')
            ->willReturn(['authorization_code_id' => 89, 'verifier' => 'valid_verifier']);
        $this->hasher->method('verifyHash')->willReturn(false);
        $this->authorization_code_revoker->expects($this->never())->method('revokeByAuthCodeId');

        $this->expectException(InvalidOAuth2RefreshTokenException::class);
        $this->revoker->revokeGrantOfRefreshToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    public function testRevokeGrantOfRefreshToken(): void
    {
        $this->refresh_token_unserializer->expects($this->once())->method('getSplitToken')
            ->willReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->expects($this->once())->method('searchRefreshTokenByApp')
            ->willReturn(['authorization_code_id' => 89, 'verifier' => 'valid_verifier']);
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->authorization_code_revoker->expects($this->once())->method('revokeByAuthCodeId')
            ->with(89);

        $this->revoker->revokeGrantOfRefreshToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    private function buildApp(): OAuth2App
    {
        return new OAuth2App(12, 'Client', 'https://example.com', false, new \Project(['group_id' => 101]));
    }
}
