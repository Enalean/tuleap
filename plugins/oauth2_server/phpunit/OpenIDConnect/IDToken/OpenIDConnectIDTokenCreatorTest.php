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

namespace Tuleap\OAuth2Server\OpenIDConnect\IDToken;

use Lcobucci\JWT\Parser;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCode;
use Tuleap\OAuth2Server\OAuth2TestScope;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\Test\Builders\UserTestBuilder;

final class OpenIDConnectIDTokenCreatorTest extends TestCase
{
    use ForgeConfigSandbox;

    private const EXPECTED_EXPIRATION_DELAY_SECONDS = 60;

    /**
     * @var OpenIDConnectIDTokenCreator
     */
    private $id_token_creator;

    protected function setUp(): void
    {
        $this->id_token_creator = new OpenIDConnectIDTokenCreator(
            OAuth2SignInScope::fromItself(),
            new JWTBuilderFactory(),
            new \DateInterval('PT' . self::EXPECTED_EXPIRATION_DELAY_SECONDS . 'S')
        );
    }

    public function testCanIssueIDToken(): void
    {
        \ForgeConfig::set('sys_https_host', 'tuleap.example.com');
        $current_time = new \DateTimeImmutable('@10');

        $payload = $this->id_token_creator->issueIDTokenFromAuthorizationCode(
            $current_time,
            $this->getApp(),
            $this->getAuthorizationCode([OAuth2SignInScope::fromItself()])
        );

        $this->assertNotNull($payload);

        $token = (new Parser())->parse($payload);
        $this->assertEquals('https://tuleap.example.com', $token->getClaim('iss'));
        $this->assertEquals('147', $token->getClaim('sub'));
        $this->assertEquals('tlp-client-id-987', $token->getClaim('aud'));
        $this->assertEquals($current_time->getTimestamp(), $token->getClaim('iat'));
        $this->assertEquals($current_time->getTimestamp() + self::EXPECTED_EXPIRATION_DELAY_SECONDS, $token->getClaim('exp'));
    }

    public function testDoesNotIssueRefreshTokenWhenAuthorizationCodeDoesNotHaveSignInScope(): void
    {
        $payload = $this->id_token_creator->issueIDTokenFromAuthorizationCode(
            new \DateTimeImmutable('@10'),
            $this->getApp(),
            $this->getAuthorizationCode([OAuth2TestScope::fromItself()])
        );

        $this->assertNull($payload);
    }

    private function getAuthorizationCode(array $scopes): OAuth2AuthorizationCode
    {
        return OAuth2AuthorizationCode::approveForSetOfScopes(
            new SplitToken(789, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
            UserTestBuilder::aUser()->withId(147)->build(),
            null,
            $scopes
        );
    }

    private function getApp(): OAuth2App
    {
        return new OAuth2App(987, 'Name', 'https://example.com', false, new \Project(['group_id' => 102]));
    }
}
