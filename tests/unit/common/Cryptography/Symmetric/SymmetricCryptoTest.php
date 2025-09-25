<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use PHPUnit\Framework\Attributes\TestWith;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidCiphertextException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SymmetricCryptoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testNoncesAreNotReused(): void
    {
        $key       = $this->getRandomEncryptionKey();
        $ad        = new EncryptionAdditionalData('table_name', 'field_name', 'id');
        $plaintext = new ConcealedString('plaintext');


        $ciphertext_1 = SymmetricCrypto::encrypt($plaintext, $ad, $key);
        $ciphertext_2 = SymmetricCrypto::encrypt($plaintext, $ad, $key);

        self::assertNotEquals($ciphertext_1, $ciphertext_2);
    }

    public function testCiphertextCanBeDecrypted(): void
    {
        $key       = $this->getRandomEncryptionKey();
        $ad        = new EncryptionAdditionalData('table_name', 'field_name', 'id');
        $plaintext = new ConcealedString('The quick brown fox jumps over the lazy dog');

        $ciphertext           = SymmetricCrypto::encrypt($plaintext, $ad, $key);
        $decrypted_ciphertext = SymmetricCrypto::decrypt($ciphertext, $ad, $key);

        self::assertEquals($plaintext->getString(), $decrypted_ciphertext->getString());
    }

    public function testACiphertextEncryptedWithADifferentKeyCannotBeDecrypted(): void
    {
        $key_1     = $this->getRandomEncryptionKey();
        $key_2     = $this->getRandomEncryptionKey();
        $ad        = new EncryptionAdditionalData('table_name', 'field_name', 'id');
        $plaintext = new ConcealedString('The quick brown fox jumps over the lazy dog');

        $ciphertext = SymmetricCrypto::encrypt($plaintext, $ad, $key_1);

        $this->expectException(InvalidCiphertextException::class);
        SymmetricCrypto::decrypt($ciphertext, $ad, $key_2);
    }

    public function testACiphertextEncryptedWithDifferentAdditionalDataCannotBeDecrypted(): void
    {
        $key       = $this->getRandomEncryptionKey();
        $ad_1      = new EncryptionAdditionalData('table_name', 'field_name', 'id');
        $ad_2      = new EncryptionAdditionalData('table_name', 'field_name', 'another_id');
        $plaintext = new ConcealedString('The quick brown fox jumps over the lazy dog');

        $ciphertext = SymmetricCrypto::encrypt($plaintext, $ad_1, $key);

        $this->expectException(InvalidCiphertextException::class);
        SymmetricCrypto::decrypt($ciphertext, $ad_2, $key);
    }

    #[TestWith(['wrongly_formatted_exception'])]
    #[TestWith(['small'])]
    public function testAWronglyFormattedCiphertextCannotBeDecrypted(string $wrong_ciphertext): void
    {
        $key = $this->getRandomEncryptionKey();

        $this->expectException(InvalidCiphertextException::class);
        SymmetricCrypto::decrypt(
            $wrong_ciphertext,
            new EncryptionAdditionalData('table_name', 'field_name', 'id'),
            $key
        );
    }

    public function testAPreviouslyEncryptedValueCanBeDecrypted(): void
    {
        $key = new EncryptionKey(new ConcealedString(str_repeat('A', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES)));

        $ad = new EncryptionAdditionalData('table_name', 'field_name', 'id');

        $plaintext = SymmetricCrypto::decrypt(
            base64_decode('npm3s3ZiXqW0RhXTsVXDZa0cTOPBX49KHcYfqBULtUEALJpXeFJt0jk2Vlt5c9OpvdyjafmhL34Oi8DzcXRQxD4qBIaSKPxLMWweekj2'),
            $ad,
            $key
        );

        self::assertEquals('Tuleap', $plaintext);
    }

    public static function getRandomEncryptionKey(): EncryptionKey
    {
        return new EncryptionKey(
            new ConcealedString(random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES))
        );
    }
}
