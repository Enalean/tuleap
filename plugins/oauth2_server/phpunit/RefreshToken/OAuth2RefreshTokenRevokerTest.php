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

namespace Tuleap\OAuth2Server\RefreshToken;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;

final class OAuth2RefreshTokenRevokerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var OAuth2RefreshTokenRevoker */
    private $revoker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SplitTokenIdentifierTranslator
     */
    private $refresh_token_unserializer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|OAuth2AuthorizationCodeRevoker
     */
    private $authorization_code_revoker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|OAuth2RefreshTokenDAO
     */
    private $dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SplitTokenVerificationStringHasher
     */
    private $hasher;

    protected function setUp(): void
    {
        $this->refresh_token_unserializer = M::mock(SplitTokenIdentifierTranslator::class);
        $this->authorization_code_revoker = M::mock(OAuth2AuthorizationCodeRevoker::class);
        $this->dao                        = M::mock(OAuth2RefreshTokenDAO::class);
        $this->hasher                     = M::mock(SplitTokenVerificationStringHasher::class);
        $this->revoker                    = new OAuth2RefreshTokenRevoker(
            $this->refresh_token_unserializer,
            $this->authorization_code_revoker,
            $this->dao,
            $this->hasher,
        );
    }

    public function testItThrowsWhenTheRefreshTokenIsNotAssociatedToTheApp(): void
    {
        $this->refresh_token_unserializer->shouldReceive('getSplitToken')
            ->once()
            ->andReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->shouldReceive('searchRefreshTokenByApp')
            ->once()
            ->andReturnNull();

        $this->expectException(OAuth2RefreshTokenNotFoundException::class);
        $this->revoker->revokeGrantOfRefreshToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    public function testItThrowsWhenTheRefreshTokenIsInvalid(): void
    {
        $this->refresh_token_unserializer->shouldReceive('getSplitToken')
            ->once()
            ->andReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->shouldReceive('searchRefreshTokenByApp')
            ->once()
            ->andReturn(['authorization_code_id' => 89, 'verifier' => 'valid_verifier']);
        $this->hasher->shouldReceive('verifyHash')->once()->andReturnFalse();

        $this->expectException(InvalidOAuth2RefreshTokenException::class);
        $this->revoker->revokeGrantOfRefreshToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    public function testRevokeGrantOfRefreshToken(): void
    {
        $this->refresh_token_unserializer->shouldReceive('getSplitToken')
            ->once()
            ->andReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->dao->shouldReceive('searchRefreshTokenByApp')
            ->once()
            ->andReturn(['authorization_code_id' => 89, 'verifier' => 'valid_verifier']);
        $this->hasher->shouldReceive('verifyHash')->once()->andReturnTrue();
        $this->authorization_code_revoker->shouldReceive('revokeByAuthCodeId')
            ->once()
            ->with(89);

        $this->revoker->revokeGrantOfRefreshToken($this->buildApp(), new ConcealedString('token_identifier'));
    }

    private function buildApp(): OAuth2App
    {
        return new OAuth2App(12, 'Client', 'https://example.com', false, new \Project(['group_id' => 101]));
    }
}
