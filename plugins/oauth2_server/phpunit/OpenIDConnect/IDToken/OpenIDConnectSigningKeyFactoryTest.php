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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

final class OpenIDConnectSigningKeyFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const SIGNING_PUBLIC_KEY  = <<<EOT
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

    /**
     * @var EncryptionKey
     */
    private $encryption_key;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OpenIDConnectSigningKeyDAO
     */
    private $dao;
    /**
     * @var OpenIDConnectSigningKeyFactory
     */
    private $signing_key_factory;

    protected function setUp(): void
    {
        $encryption_key_factory = \Mockery::mock(KeyFactory::class);
        $this->encryption_key   = new EncryptionKey(new ConcealedString(\random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
        $encryption_key_factory->shouldReceive('getEncryptionKey')->andReturn($this->encryption_key);

        $this->dao                 = \Mockery::mock(OpenIDConnectSigningKeyDAO::class);
        $this->signing_key_factory = new OpenIDConnectSigningKeyFactory($encryption_key_factory, $this->dao);
    }

    public function testGetExistingSigningPrivateKeyFromTheDB(): void
    {
        $this->dao->shouldReceive('searchEncryptedPrivateKey')->once()->andReturn(
            SymmetricCrypto::encrypt(new ConcealedString(self::SIGNING_PRIVATE_KEY), $this->encryption_key)
        );

        $key = $this->signing_key_factory->getKey();

        $this->assertEquals(self::SIGNING_PRIVATE_KEY, $key->getContent());
    }

    public function testGetExistingSigningPublicKeyFromTheDB(): void
    {
        $this->dao->shouldReceive('searchPublicKey')->once()->andReturn(self::SIGNING_PUBLIC_KEY);

        $public_key = $this->signing_key_factory->getPublicKey();

        $this->assertEquals(self::SIGNING_PUBLIC_KEY, $public_key);
    }

    public function testCreateNewSigningKeyWhenNoneAlreadyExistBeforeReturningPrivateKey(): void
    {
        $this->dao->shouldReceive('searchEncryptedPrivateKey')->once()->andReturn(null);
        $this->dao->shouldReceive('save')->once();

        $key = $this->signing_key_factory->getKey();
        $this->assertNotEmpty($key->getContent());
    }

    public function testCreateNewSigningKeyWhenNoneAlreadyExistBeforeReturningPublicKey(): void
    {
        $this->dao->shouldReceive('searchPublicKey')->once()->andReturn(null);
        $this->dao->shouldReceive('save')->once();

        $public_key = $this->signing_key_factory->getPublicKey();
        $this->assertNotEmpty($public_key);
    }
}
