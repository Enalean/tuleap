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

namespace Tuleap\OAuth2Server\Grant;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\User\OAuth2\Scope\DemoOAuth2Scope;

final class OAuth2AuthorizationCodeVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var OAuth2AuthorizationCodeVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->user_manager = \Mockery::mock(\UserManager::class);
        $this->verifier     = new OAuth2AuthorizationCodeVerifier(
            new SplitTokenVerificationStringHasher(),
            $this->user_manager
        );
    }

    public function testGivingTheCorrectTestAuthCodeRetrievesAValidAuthCodeForTheDemoScope(): void
    {
        $expected_user = new \PFUser(['language_id' => 'en']);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturn($expected_user);

        $auth_code = new SplitToken(
            1,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );

        $verified_authorization = $this->verifier->getAuthorizationCode($auth_code);

        $this->assertSame($expected_user, $verified_authorization->getUser());
        $this->assertEquals([DemoOAuth2Scope::fromItself()], $verified_authorization->getScopes());
    }

    public function testVerificationFailsWhenAuthCodeCannotBeFound(): void
    {
        $auth_code = new SplitToken(
            404,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );

        $this->expectException(OAuth2AuthCodeNotFoundException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $auth_code = new SplitToken(
            1,
            new SplitTokenVerificationString(new ConcealedString('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'))
        );

        $this->expectException(InvalidOAuth2AuthCodeException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }
}
