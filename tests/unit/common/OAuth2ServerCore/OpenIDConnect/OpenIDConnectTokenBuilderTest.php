<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2ServerCore\OpenIDConnect;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Tuleap\ForgeConfigSandbox;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\ServerHostname;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OpenIDConnectTokenBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    private const EXPECTED_EXPIRATION_DELAY_SECONDS = 60;
    private const CURRENT_TIME_TIMESTAMP            = 10;

    public function testIssueToken(): void
    {
        \ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, 'tuleap.example.com');

        $token = self::getToken(['val_str' => 'mystring', 'val_int' => 99]);

        self::assertEquals('https://tuleap.example.com', $token->claims()->get('iss'));
        self::assertEquals('102', $token->claims()->get('sub'));
        self::assertEquals(['tlp-client-id-64'], $token->claims()->get('aud'));
        self::assertEquals(new \DateTimeImmutable('@' . self::CURRENT_TIME_TIMESTAMP), $token->claims()->get('iat'));
        self::assertEquals(new \DateTimeImmutable('@' . (self::CURRENT_TIME_TIMESTAMP + self::EXPECTED_EXPIRATION_DELAY_SECONDS)), $token->claims()->get('exp'));
        self::assertEquals(OpenIDConnectSigningKeyFactoryStaticForTestPurposes::SIGNING_PUBLIC_KEY_FINGERPRINT, $token->headers()->get('kid'));
        self::assertEquals('mystring', $token->claims()->get('val_str'));
        self::assertEquals(99, $token->claims()->get('val_int'));
    }

    public function testAdditionalClaimsCannotOverwriteTheDefaultOnes(): void
    {
        $token = self::getToken(['kid' => 'invalid']);

        self::assertEquals(OpenIDConnectSigningKeyFactoryStaticForTestPurposes::SIGNING_PUBLIC_KEY_FINGERPRINT, $token->headers()->get('kid'));
    }

    /**
     * @param array<string, mixed> $claims
     */
    private static function getToken(array $claims): UnencryptedToken
    {
        $signer        = new Sha256();
        $token_builder = new OpenIDConnectTokenBuilder(
            new JWTBuilderFactory(),
            new OpenIDConnectSigningKeyFactoryStaticForTestPurposes(),
            new \DateInterval('PT' . self::EXPECTED_EXPIRATION_DELAY_SECONDS . 'S'),
            $signer
        );

        $token_str = $token_builder->getToken(
            new \DateTimeImmutable('@' . self::CURRENT_TIME_TIMESTAMP),
            new OAuth2App(64, 'Test', 'https://oauth2.example.com/redirect', true, null),
            UserTestBuilder::anActiveUser()->withId(102)->build(),
            $claims
        );

        $token = (new Parser(new JoseEncoder()))->parse($token_str);

        $validator = new Validator();
        self::assertTrue($validator->validate($token, new SignedWith($signer, InMemory::plainText(OpenIDConnectSigningKeyFactoryStaticForTestPurposes::SIGNING_PUBLIC_KEY))));

        assert($token instanceof UnencryptedToken);
        return $token;
    }
}
