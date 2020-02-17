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

namespace Tuleap\User\OAuth2\AccessToken;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;

final class OAuth2AccessTokenVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var OAuth2AccessTokenVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->user_manager = \Mockery::mock(\UserManager::class);

        $this->verifier = new OAuth2AccessTokenVerifier($this->user_manager, new SplitTokenVerificationStringHasher());
    }

    public function testGivingTheCorrectTestTokenRetrievesTheAnonymousUser(): void
    {
        $expected_user = new \PFUser(['language_id' => 'en']);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturn($expected_user);

        $access_token = new SplitToken(
            1,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $user = $this->verifier->getUser($access_token);

        $this->assertSame($expected_user, $user);
    }

    public function testVerificationFailsWhenTokenCanNotBeFound(): void
    {
        $access_token = \Mockery::mock(SplitToken::class);
        $access_token->shouldReceive('getID')->andReturn(404);

        $this->expectException(OAuth2AccessTokenNotFoundException::class);
        $this->verifier->getUser($access_token);
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $access_token = new SplitToken(
            1,
            new SplitTokenVerificationString(new ConcealedString('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'))
        );

        $this->expectException(InvalidOAuth2AccessTokenException::class);
        $this->verifier->getUser($access_token);
    }
}
