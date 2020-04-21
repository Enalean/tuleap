<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\REST;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\Exception\InvalidKeyException;
use Tuleap\DynamicCredentials\Plugin\PluginInfo;

class RequestSignatureVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public const PUBLIC_KEY  = 'ka7Gcvo3RO0FeksfVkBCgTndCz/IMLfwCQA3DoN8k68=';
    public const SECRET_KEY  = 'KOJqKTCvuBvSdKN/MgGLlTI7T3hrZKERlq2JDLB7Wc+RrsZy+jdE7QV6Sx9WQEKBOd0LP8gwt/AJADcOg3yTrw==';
    public const USED_DOMAIN = 'example.com';

    public function setUp(): void
    {
        parent::setUp();
        \ForgeConfig::store();
        \ForgeConfig::set('sys_https_host', self::USED_DOMAIN);
    }

    public function tearDown(): void
    {
        \ForgeConfig::restore();
        parent::tearDown();
    }

    /**
     * @dataProvider signedParameterProvider
     */
    public function testSignatureIsValid($signature, $parameter, $expected_result)
    {
        $plugin_info = Mockery::mock(PluginInfo::class);
        $plugin_info->shouldReceive('getPropertyValueForName')->andReturn(self::PUBLIC_KEY);

        $request_signature_verifier = new RequestSignatureVerifier($plugin_info);

        $this->assertEquals($expected_result, $request_signature_verifier->isSignatureValid($signature, $parameter));
    }

    public function signedParameterProvider()
    {
        return [
            [$this->getSignature('param'), 'param', true],
            [$this->getSignature('invalid_signature_for_message'), 'param', false],
            ['not_even_base64_encoded_signature', 'param', false],
            ['QWxwYWNhcw==', 'param', false],
            ['WxwYWNhcyBhcmUgYW1hemluZw==', 'signature_with_invalid_base64', false],
        ];
    }

    /**
     * @return string
     */
    private function getSignature($parameter)
    {
        $secret_key_decoded = base64_decode(self::SECRET_KEY);
        return base64_encode(sodium_crypto_sign_detached(self::USED_DOMAIN . $parameter, $secret_key_decoded));
    }

    public function testRejectionWhenInvalidPublicKeyIsGiven()
    {
        $plugin_info = Mockery::mock(PluginInfo::class);
        $plugin_info->shouldReceive('getPropertyValueForName')->andReturn('invalid_public_key');

        $this->expectException(InvalidKeyException::class);

        new RequestSignatureVerifier($plugin_info);
    }
}
