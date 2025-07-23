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

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;
use Tuleap\User\OAuth2\AccessToken\InvalidOAuth2AccessTokenException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenNotFoundException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2AccessTokenRevokerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var OAuth2AccessTokenRevoker */
    private $revoker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenIdentifierTranslator
     */
    private $access_token_unserializer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AccessTokenDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AuthorizationCodeRevoker
     */
    private $authorization_code_revoker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenVerificationStringHasher
     */
    private $hasher;

    #[\Override]
    protected function setUp(): void
    {
        $this->access_token_unserializer  = $this->createMock(SplitTokenIdentifierTranslator::class);
        $this->authorization_code_revoker = $this->createMock(OAuth2AuthorizationCodeRevoker::class);
        $this->dao                        = $this->createMock(OAuth2AccessTokenDAO::class);
        $this->hasher                     = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->revoker                    = new OAuth2AccessTokenRevoker(
            $this->access_token_unserializer,
            $this->authorization_code_revoker,
            $this->dao,
            $this->hasher,
        );
    }

    public function testItThrowsWhenTheAccessTokenIsNotAssociatedToTheApp(): void
    {
        $this->access_token_unserializer->expects($this->once())->method('getSplitToken')
            ->willReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->expects($this->once())->method('searchAccessTokenByApp')
            ->willReturn(null);

        $this->expectException(OAuth2AccessTokenNotFoundException::class);
        $this->revoker->revokeGrantOfAccessToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    public function testItThrowsWhenTheAccessTokenIsInvalid(): void
    {
        $this->access_token_unserializer->expects($this->once())->method('getSplitToken')
            ->willReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->expects($this->once())->method('searchAccessTokenByApp')
            ->willReturn(['authorization_code_id' => 89, 'verifier' => 'valid_verifier']);
        $this->hasher->expects($this->once())->method('verifyHash')->willReturn(false);

        $this->expectException(InvalidOAuth2AccessTokenException::class);
        $this->revoker->revokeGrantOfAccessToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    public function testRevokeGrantOfAccessToken(): void
    {
        $this->access_token_unserializer->expects($this->once())->method('getSplitToken')
            ->willReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->expects($this->once())->method('searchAccessTokenByApp')
            ->willReturn(['authorization_code_id' => 89, 'verifier' => 'valid_verifier']);
        $this->hasher->expects($this->once())->method('verifyHash')->willReturn(true);
        $this->authorization_code_revoker->expects($this->once())->method('revokeByAuthCodeId')
            ->with(89);

        $this->revoker->revokeGrantOfAccessToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    private function buildApp(): OAuth2App
    {
        return new OAuth2App(12, 'Client', 'https://example.com', false, new \Project(['group_id' => 101]));
    }
}
