<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Cryptography\Symmetric;

use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidCiphertextException;

final class SymmetricCryptoTest extends TestCase
{
    public function testItCannotBeInstantiated(): void
    {
        $this->expectException(\RuntimeException::class);
        new SymmetricCrypto();
    }

    public function testNoncesAreNotReused(): void
    {
        $key       = new EncryptionKey(
            new ConcealedString(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES))
        );
        $plaintext = new ConcealedString('plaintext');

        $ciphertext_1 = SymmetricCrypto::encrypt($plaintext, $key);
        $ciphertext_2 = SymmetricCrypto::encrypt($plaintext, $key);

        $this->assertNotEquals($ciphertext_1, $ciphertext_2);
    }

    public function testCiphertextCanBeDecrypted(): void
    {
        $key       = new EncryptionKey(
            new ConcealedString(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES))
        );
        $plaintext = new ConcealedString('The quick brown fox jumps over the lazy dog');

        $ciphertext           = SymmetricCrypto::encrypt($plaintext, $key);
        $decrypted_ciphertext = SymmetricCrypto::decrypt($ciphertext, $key);

        $this->assertEquals($plaintext->getString(), $decrypted_ciphertext->getString());
    }

    public function testACiphertextEncryptedWithADifferentKeyCannotBeDecrypted(): void
    {
        $key_1     = new EncryptionKey(
            new ConcealedString(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES))
        );
        $key_2     = new EncryptionKey(
            new ConcealedString(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES))
        );
        $plaintext = new ConcealedString('The quick brown fox jumps over the lazy dog');

        $ciphertext = SymmetricCrypto::encrypt($plaintext, $key_1);

        $this->expectException(InvalidCiphertextException::class);
        SymmetricCrypto::decrypt($ciphertext, $key_2);
    }

    public function testAWronglyFormattedCiphertextCannotBeDecrypted(): void
    {
        $key = new EncryptionKey(
            new ConcealedString(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES))
        );

        $this->expectException(InvalidCiphertextException::class);
        SymmetricCrypto::decrypt('wrongly_formatted_exception', $key);
    }

    public function testAPreviouslyEncryptedValueCanBeDecrypted(): void
    {
        $key = new EncryptionKey(new ConcealedString(base64_decode('8sgzyjKu2S90GmxShUuWcFOpum6nIZzlCAoxn3MZdwU=')));

        $plaintext = SymmetricCrypto::decrypt(
            base64_decode('MVlvotdhkOe0SkdxOqbpcCD0iB/o224T1REmpcm4sS7VRklvXL0z0HPHt90TNg=='),
            $key
        );

        $this->assertEquals('Tuleap', $plaintext);
    }
}
