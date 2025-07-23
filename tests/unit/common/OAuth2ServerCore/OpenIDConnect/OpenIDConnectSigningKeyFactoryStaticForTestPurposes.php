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

use Tuleap\Cryptography\ConcealedString;

final class OpenIDConnectSigningKeyFactoryStaticForTestPurposes implements OpenIDConnectSigningKeyFactory
{
    public const SIGNING_PUBLIC_KEY_FINGERPRINT = '13e908c0c14b52fa364f6573cda85971d16de83b17d6ef8793447724c464c01c';
    public const SIGNING_PUBLIC_KEY             = <<<EOT
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
    public const SIGNING_PRIVATE_KEY            = <<<EOT
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

    private SigningPublicKey $public_key;
    private SigningPrivateKey $private_key;

    public function __construct()
    {
        $this->public_key  = SigningPublicKey::fromPEMFormat(self::SIGNING_PUBLIC_KEY);
        $this->private_key = new SigningPrivateKey($this->public_key, new ConcealedString(self::SIGNING_PRIVATE_KEY));
    }

    #[\Override]
    public function getPublicKeys(\DateTimeImmutable $current_time): array
    {
        return [$this->public_key];
    }

    #[\Override]
    public function getKey(\DateTimeImmutable $current_time): SigningPrivateKey
    {
        return $this->private_key;
    }
}
