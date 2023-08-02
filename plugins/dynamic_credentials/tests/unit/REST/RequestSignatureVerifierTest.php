<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\REST;

use Tuleap\Cryptography\Asymmetric\SignaturePublicKey;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Option\Option;

final class RequestSignatureVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    public const PUBLIC_KEY  = 'ka7Gcvo3RO0FeksfVkBCgTndCz/IMLfwCQA3DoN8k68=';
    public const SECRET_KEY  = 'KOJqKTCvuBvSdKN/MgGLlTI7T3hrZKERlq2JDLB7Wc+RrsZy+jdE7QV6Sx9WQEKBOd0LP8gwt/AJADcOg3yTrw==';
    public const USED_DOMAIN = 'example.com';

    protected function setUp(): void
    {
        parent::setUp();
        \ForgeConfig::set('sys_default_domain', self::USED_DOMAIN);
    }

    /**
     * @dataProvider signedParameterProvider
     */
    public function testSignatureIsValid(string $signature, string $parameter, bool $expected_result): void
    {
        $request_signature_verifier = new RequestSignatureVerifier(
            Option::fromValue(new SignaturePublicKey(new ConcealedString(base64_decode(self::PUBLIC_KEY))))
        );

        self::assertEquals($expected_result, $request_signature_verifier->isSignatureValid($signature, $parameter));
    }

    public static function signedParameterProvider(): array
    {
        return [
            [self::getSignature('param'), 'param', true],
            [self::getSignature('invalid_signature_for_message'), 'param', false],
            ['not_even_base64_encoded_signature', 'param', false],
            ['QWxwYWNhcw==', 'param', false],
            ['WxwYWNhcyBhcmUgYW1hemluZw==', 'signature_with_invalid_base64', false],
        ];
    }

    private static function getSignature(string $parameter): string
    {
        $secret_key_decoded = base64_decode(self::SECRET_KEY);
        return base64_encode(sodium_crypto_sign_detached(self::USED_DOMAIN . $parameter, $secret_key_decoded));
    }

    public function testRejectionWhenNoPublicIsGiven(): void
    {
        $request_signature_verifier = new RequestSignatureVerifier(Option::nothing(SignaturePublicKey::class));
        self::assertFalse($request_signature_verifier->isSignatureValid(self::getSignature('param'), 'param'));
    }
}
