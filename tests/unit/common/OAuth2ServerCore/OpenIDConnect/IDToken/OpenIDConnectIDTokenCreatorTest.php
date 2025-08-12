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

namespace Tuleap\OAuth2ServerCore\OpenIDConnect\IDToken;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCode;
use Tuleap\OAuth2ServerCore\OAuth2TestScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\JWTBuilderFactory;
use Tuleap\OAuth2ServerCore\OpenIDConnect\OpenIDConnectSigningKeyFactoryStaticForTestPurposes;
use Tuleap\OAuth2ServerCore\OpenIDConnect\OpenIDConnectTokenBuilder;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OpenIDConnectIDTokenCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private const EXPECTED_EXPIRATION_DELAY_SECONDS = 60;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var OpenIDConnectIDTokenCreator
     */
    private $id_token_creator;

    #[\Override]
    protected function setUp(): void
    {
        $this->user_manager = $this->createMock(\UserManager::class);

        $this->id_token_creator = new OpenIDConnectIDTokenCreator(
            OAuth2SignInScope::fromItself(),
            new OpenIDConnectTokenBuilder(
                new JWTBuilderFactory(),
                new OpenIDConnectSigningKeyFactoryStaticForTestPurposes(),
                new \DateInterval('PT' . self::EXPECTED_EXPIRATION_DELAY_SECONDS . 'S'),
                new Sha256()
            ),
            $this->user_manager
        );
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['nonce'])]
    #[\PHPUnit\Framework\Attributes\TestWith([null])]
    public function testCanIssueIDToken(?string $nonce): void
    {
        \ForgeConfig::set('sys_default_domain', 'tuleap.example.com');
        $current_time = new \DateTimeImmutable('@10');

        $this->user_manager->method('getUserAccessInfo')->willReturn(['last_auth_success' => '5']);

        $payload = $this->id_token_creator->issueIDTokenFromAuthorizationCode(
            $current_time,
            $this->getApp(),
            $this->getAuthorizationCode([OAuth2SignInScope::fromItself()], $nonce)
        );

        $this->assertNotNull($payload);

        $token = (new Parser(new JoseEncoder()))->parse($payload);
        assert($token instanceof UnencryptedToken);
        $this->assertEquals('https://tuleap.example.com', $token->claims()->get('iss'));
        $this->assertEquals('147', $token->claims()->get('sub'));
        $this->assertEquals(['tlp-client-id-987'], $token->claims()->get('aud'));
        $this->assertEquals($current_time, $token->claims()->get('iat'));
        $this->assertEquals($current_time->add(new \DateInterval('PT' . self::EXPECTED_EXPIRATION_DELAY_SECONDS . 'S')), $token->claims()->get('exp'));
        $this->assertEquals(5, $token->claims()->get('auth_time'));
        if ($nonce === null) {
            $this->assertFalse($token->claims()->has('nonce'));
        } else {
            $this->assertEquals($nonce, $token->claims()->get('nonce'));
        }
        $this->assertEquals(OpenIDConnectSigningKeyFactoryStaticForTestPurposes::SIGNING_PUBLIC_KEY_FINGERPRINT, $token->headers()->get('kid'));
        $validator = new Validator();
        self::assertTrue($validator->validate($token, new SignedWith(new Sha256(), InMemory::plainText(OpenIDConnectSigningKeyFactoryStaticForTestPurposes::SIGNING_PUBLIC_KEY))));
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
