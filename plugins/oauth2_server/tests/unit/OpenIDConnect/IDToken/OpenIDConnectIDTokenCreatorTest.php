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
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
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
    use MockeryPHPUnitIntegration;

    private const SIGNING_PUBLIC_KEY = <<<EOT
        -----BEGIN PUBLIC KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApVp45DC1lniS5l9yiR81
        OM3BCESDLyZYX3pXS32oJz0eOIqgA4mnqGNvupo/ARJnu1W/KVNNqxBNGno1oNLg
        V3GkHULBV+D4NDaX4064I0k1dk0HZBd8OG8QB0dwFoNFZ19SNrsEyq4xFn3CIysl
        lfFE6GVQVht84/etmvO5+p4Dj6kUM4FO46jBXQBxSQs7ErE22m67CViu9ApDjZ1W
        9e7mHItPZfw0ldH6Y6+ZXfz8SBs/lblm/1BST1C7l/5vQtjStgHmiGlVL6CRIzyx
        DCJKYKP1r0FrwUEnMJEU1h+MyMSKPP9gzln8+icbhSvQF/eX6oZCfl+ibrC/nRZf
        2QIDAQAB
        -----END PUBLIC KEY-----
        EOT;

    private const SIGNING_PRIVATE_KEY = <<<EOT
        -----BEGIN PRIVATE KEY-----
        MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQClWnjkMLWWeJLm
        X3KJHzU4zcEIRIMvJlhfeldLfagnPR44iqADiaeoY2+6mj8BEme7Vb8pU02rEE0a
        ejWg0uBXcaQdQsFX4Pg0NpfjTrgjSTV2TQdkF3w4bxAHR3AWg0VnX1I2uwTKrjEW
        fcIjKyWV8UToZVBWG3zj962a87n6ngOPqRQzgU7jqMFdAHFJCzsSsTbabrsJWK70
        CkONnVb17uYci09l/DSV0fpjr5ld/PxIGz+VuWb/UFJPULuX/m9C2NK2AeaIaVUv
        oJEjPLEMIkpgo/WvQWvBQScwkRTWH4zIxIo8/2DOWfz6JxuFK9AX95fqhkJ+X6Ju
        sL+dFl/ZAgMBAAECggEAT7af1RIOWG3kE58r7iLXW30Fc+DjhRVtQQoPj1sSd2gl
        a4iYv1vbMXhOYpz9hpzC2TLrJxb7uF3xbbRAqjk+4ajtPxXxc1YHEdTHwFMwvgIK
        /e8AgyY3QlV4Wqn7xT6fdMglMDFUjAkRrRAPSTkBs5lOaOJ+qiQyPwwl6y9YFxLT
        VjpCL6WgFVobwCljdjmhyFiwItvoya5ZbgeHQoSOXTx6rZh44JHIhJNKcPi+WzSL
        mqiKP8LoDWtysBof1NQg6/DopWP5JR4Ia7t5SuURggYwj8OwDbIWN2jP7fG1ptJP
        aot1Q3wt4B2Kl7T0NbtVo8AC4FDDiwCP+mWt0R3YAQKBgQDacWJJidSDex41EUzp
        L1rkY6GgLd0kYgFm5jNNpLqZVnqjjEO2pfxoDqgV6lm+0qCLmHMjeKFmG8cBrpSY
        adyO4lF+Fho2TlyUV/wJSoAFUbC49JuAAgeo8o2UxeUdUkDIHH6kp+BXPXH70EfP
        c8ttQtORtJ0vDJ97FH4xCdl2gQKBgQDByGPcdRK2vQDXDMxQJUAyG6J46HbaIxwz
        +OTvghQtR/xSsARjYGF6P8uyfmXrPI/nExfXE0euj8ozit3xX2XP+fb3W+KUS7As
        pNrssO7fXeEwAq2pP3bCdHn7iU2jJPhvnnVyE9ZgaNWjSmJmtE+3MLBkZRqmajbR
        x2jszeetWQKBgFz7FlMnEAZHSbxc+NfpCE9e+VUtMIxkCyS5p+zMyYCrhthGxCvi
        y2Wfl3x8nGbVUPEamyfmGQ1VlYfpv+aAaRmIzBdXYSDsigu6x9VMmOGqvAZ+WBJM
        yuXnGMzSz4uDj3+eYWnE64E27mW5alerelOvtk63CpEUVm4VcwF8p8wBAoGAN+q6
        HiBOMRrixis0PaAyIQNmY5s4yIM/HSQh85bGebZ+8eFGsuJZ3mvQPIZKpJGKOLSC
        uZYfphhp0Wut1Xugpl3LzN7fx8j7YjaD0a7QjvXJCBCNyfu9KilwFYwuMfh2E8dW
        vn9I6fL2SrMpJ9e59POAwseF5CVcAjaXaVWVF6kCgYBB2IJir/uvNdPG3+b+I9tY
        RSsoVhE3f11ai1oBJQgRR/y9dbjBjVdK0z1CuD9y3uqVrey7q9pbwFpfeXMuQ96G
        ayv7TvmcUFl04tPTVXjhi5JiAx19ujzqfdlZkZRLR0LG1V5LSfuYpO0y5DZciPPy
        P4TipPm61EAWju6O/LnxKA==
        -----END PRIVATE KEY-----
        EOT;

    private const EXPECTED_EXPIRATION_DELAY_SECONDS = 60;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var OpenIDConnectIDTokenCreator
     */
    private $id_token_creator;

    protected function setUp(): void
    {
        $signing_key_factory = \Mockery::mock(OpenIDConnectSigningKeyFactory::class);
        $signing_key_factory->shouldReceive('getKey')->andReturn(new Key(self::SIGNING_PRIVATE_KEY));

        $this->user_manager = \Mockery::mock(\UserManager::class);

        $this->id_token_creator = new OpenIDConnectIDTokenCreator(
            OAuth2SignInScope::fromItself(),
            new JWTBuilderFactory(),
            new \DateInterval('PT' . self::EXPECTED_EXPIRATION_DELAY_SECONDS . 'S'),
            $signing_key_factory,
            new Sha256(),
            $this->user_manager
        );
    }

    /**
     * @testWith ["nonce"]
     *           [null]
     */
    public function testCanIssueIDToken(?string $nonce): void
    {
        \ForgeConfig::set('sys_https_host', 'tuleap.example.com');
        $current_time = new \DateTimeImmutable('@10');

        $this->user_manager->shouldReceive('getUserAccessInfo')->andReturn(['last_auth_success' => '5']);

        $payload = $this->id_token_creator->issueIDTokenFromAuthorizationCode(
            $current_time,
            $this->getApp(),
            $this->getAuthorizationCode([OAuth2SignInScope::fromItself()], $nonce)
        );

        $this->assertNotNull($payload);

        $token = (new Parser())->parse($payload);
        $this->assertEquals('https://tuleap.example.com', $token->getClaim('iss'));
        $this->assertEquals('147', $token->getClaim('sub'));
        $this->assertEquals('tlp-client-id-987', $token->getClaim('aud'));
        $this->assertEquals($current_time->getTimestamp(), $token->getClaim('iat'));
        $this->assertEquals($current_time->getTimestamp() + self::EXPECTED_EXPIRATION_DELAY_SECONDS, $token->getClaim('exp'));
        $this->assertEquals(5, $token->getClaim('auth_time'));
        if ($nonce === null) {
            $this->assertFalse($token->hasClaim('nonce'));
        } else {
            $this->assertEquals($nonce, $token->getClaim('nonce'));
        }
        $this->assertTrue($token->verify(new Sha256(), self::SIGNING_PUBLIC_KEY));
    }

    public function testDoesNotIssueRefreshTokenWhenAuthorizationCodeDoesNotHaveSignInScope(): void
    {
        $payload = $this->id_token_creator->issueIDTokenFromAuthorizationCode(
            new \DateTimeImmutable('@10'),
            $this->getApp(),
            $this->getAuthorizationCode([OAuth2TestScope::fromItself()], 'nonce')
        );

        $this->assertNull($payload);
    }

    private function getAuthorizationCode(array $scopes, ?string $nonce): OAuth2AuthorizationCode
    {
        return OAuth2AuthorizationCode::approveForSetOfScopes(
            new SplitToken(789, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
            UserTestBuilder::aUser()->withId(147)->build(),
            null,
            $nonce,
            $scopes
        );
    }

    private function getApp(): OAuth2App
    {
        return new OAuth2App(987, 'Name', 'https://example.com', false, new \Project(['group_id' => 102]));
    }
}
