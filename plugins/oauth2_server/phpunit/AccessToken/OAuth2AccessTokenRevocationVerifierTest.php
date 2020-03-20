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

namespace Tuleap\OAuth2Server\AccessToken;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\User\OAuth2\AccessToken\InvalidOAuth2AccessTokenException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenNotFoundException;

final class OAuth2AccessTokenRevocationVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var OAuth2AccessTokenRevocationVerifier
     */
    private $verifier;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|OAuth2AccessTokenDAO
     */
    private $access_token_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SplitTokenVerificationStringHasher
     */
    private $hasher;

    protected function setUp(): void
    {
        $this->access_token_dao = M::mock(OAuth2AccessTokenDAO::class);
        $this->hasher           = M::mock(SplitTokenVerificationStringHasher::class);
        $this->verifier         = new OAuth2AccessTokenRevocationVerifier($this->access_token_dao, $this->hasher);
    }

    public function testGetAssociatedAuthorizationCodeIDThrowsWhenAccessTokenAssociatedToAppCantBeFound(): void
    {
        $access_token = new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString());
        $app          = new OAuth2App(114, 'Client', 'https://example.com', false, new \Project(['group_id' => 101]));
        $this->access_token_dao->shouldReceive('searchAccessTokenByApp')
            ->with(12, 114)
            ->once()
            ->andReturnNull();

        $this->expectException(OAuth2AccessTokenNotFoundException::class);
        $this->verifier->getAssociatedAuthorizationCodeID($access_token, $app);
    }

    public function testGetAssociatedAuthorizationCodeIDThrowsWhenAccessTokenIsInvalid(): void
    {
        $access_token = new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString());
        $app          = new OAuth2App(114, 'Client', 'https://example.com', false, new \Project(['group_id' => 101]));
        $this->access_token_dao->shouldReceive('searchAccessTokenByApp')
            ->with(12, 114)
            ->once()
            ->andReturn(['authorization_code_id' => 34, 'verifier' => 'wrong_hashed_verification_string']);
        $this->hasher->shouldReceive('verifyHash')->andReturnFalse();

        $this->expectException(InvalidOAuth2AccessTokenException::class);
        $this->verifier->getAssociatedAuthorizationCodeID($access_token, $app);
    }

    public function testGetAssociatedAuthorizationCodeID(): void
    {
        $verifier     = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $access_token = new SplitToken(12, $verifier);
        $app          = new OAuth2App(114, 'Client', 'https://example.com', false, new \Project(['group_id' => 101]));
        $this->access_token_dao->shouldReceive('searchAccessTokenByApp')
            ->with(12, 114)
            ->once()
            ->andReturn(['authorization_code_id' => 34, 'verifier' => 'expected_hashed_verification_string']);
        $this->hasher->shouldReceive('verifyHash')->andReturnTrue();

        $authorization_code_id = $this->verifier->getAssociatedAuthorizationCodeID($access_token, $app);
        $this->assertSame(34, $authorization_code_id);
    }
}
