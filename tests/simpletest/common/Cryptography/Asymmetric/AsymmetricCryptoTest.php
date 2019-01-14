<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
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

namespace Tuleap\Cryptography\Asymmetric;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidSignatureException;

class AsymmetricCryptoTest extends \TuleapTestCase
{
    public function itCannotBeInstantiated() : void
    {
        $this->expectException('RuntimeException');
        new AsymmetricCrypto();
    }

    public function itCanVerifyASignedMessage() : void
    {
        $key_pair   = \sodium_crypto_sign_keypair();
        $secret_key = new SignatureSecretKey(new ConcealedString(\sodium_crypto_sign_secretkey($key_pair)));
        $public_key = new SignaturePublicKey(new ConcealedString(\sodium_crypto_sign_publickey($key_pair)));

        $message            = 'The quick brown fox jumps over the lazy dog';
        $signature          = AsymmetricCrypto::sign($message, $secret_key);
        $is_signature_valid = AsymmetricCrypto::verify($message, $public_key, $signature);

        $this->assertTrue($is_signature_valid);
    }

    public function itDoesNotVerifyAnInvalidSignedMessage() : void
    {
        $secret_key1 = new SignatureSecretKey(
            new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SIGN_SECRETKEYBYTES))
        );
        $public_key2 = new SignaturePublicKey(
            new ConcealedString(str_repeat('b', SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES))
        );

        $message            = 'The quick brown fox jumps over the lazy dog';
        $signature          = AsymmetricCrypto::sign($message, $secret_key1);
        $is_signature_valid = AsymmetricCrypto::verify($message, $public_key2, $signature);

        $this->assertFalse($is_signature_valid);
    }

    public function itRejectsInvalidSignature() : void
    {
        $public_key = \Mockery::mock(SignaturePublicKey::class);

        $message   = 'The quick brown fox jumps over the lazy dog';
        $signature = 'invalid_signature';

        $this->expectException(InvalidSignatureException::class);
        AsymmetricCrypto::verify($message, $public_key, $signature);
    }
}
