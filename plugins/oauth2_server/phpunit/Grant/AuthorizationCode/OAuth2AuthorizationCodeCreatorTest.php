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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\App\OAuth2App;

final class OAuth2AuthorizationCodeCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const EXPECTED_EXPIRATION_DELAY_SECONDS = 30;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2AuthorizationCodeDAO
     */
    private $dao;
    /**
     * @var OAuth2AuthorizationCodeCreator
     */
    private $auth_code_creator;


    protected function setUp(): void
    {
        $this->dao = \Mockery::mock(OAuth2AuthorizationCodeDAO::class);

        $formatter = new class implements SplitTokenFormatter
        {
            public function getIdentifier(SplitToken $token) : ConcealedString
            {
                return $token->getVerificationString()->getString();
            }
        };

        $this->auth_code_creator = new OAuth2AuthorizationCodeCreator(
            $formatter,
            new SplitTokenVerificationStringHasher(),
            $this->dao,
            new \DateInterval('PT' . self::EXPECTED_EXPIRATION_DELAY_SECONDS . 'S')
        );
    }

    public function testCanCreateAnAuthorizationCode(): void
    {
        $current_time = new \DateTimeImmutable('@10');

        $this->dao->shouldReceive('create')
            ->with(12, 102, \Mockery::any(), $current_time->getTimestamp() + self::EXPECTED_EXPIRATION_DELAY_SECONDS)
            ->once()->andReturn(1);

        $auth_code = $this->auth_code_creator->createAuthorizationCodeIdentifier(
            $current_time,
            $this->buildOAuth2App(),
            new \PFUser(['language_id' => 'en', 'user_id' => '102'])
        );

        $this->assertNotEmpty($auth_code->getString());
    }

    public function testAuthCodeIdentifierIsDifferentEachTimeAuAuthorizationCodeIsCreated(): void
    {
        $current_time = new \DateTimeImmutable('@10');

        $this->dao->shouldReceive('create')->andReturn(2, 3);

        $auth_code_1 = $this->auth_code_creator->createAuthorizationCodeIdentifier(
            $current_time,
            $this->buildOAuth2App(),
            new \PFUser(['language_id' => 'en', 'user_id' => '102'])
        );
        $auth_code_2 = $this->auth_code_creator->createAuthorizationCodeIdentifier(
            $current_time,
            $this->buildOAuth2App(),
            new \PFUser(['language_id' => 'en', 'user_id' => '103'])
        );

        $this->assertFalse($auth_code_1->isIdenticalTo($auth_code_2));
    }

    private function buildOAuth2App(): OAuth2App
    {
        return new OAuth2App(12, 'Name', 'https://example.com', new Project(['group_id' => 102]));
    }
}
