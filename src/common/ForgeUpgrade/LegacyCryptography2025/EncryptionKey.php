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

namespace Tuleap\ForgeUpgrade\LegacyCryptography2025;

final readonly class EncryptionKey
{
    private function __construct(
        #[\SensitiveParameter]
        private string $raw_content,
    ) {
    }

    public static function build(): ?self
    {
        $key_path = \ForgeConfig::get('sys_custom_dir') . '/conf/encryption_secret.key';

        $raw_content_hex = @\file_get_contents($key_path);
        if ($raw_content_hex === false) {
            return null;
        }

        $raw_content = \sodium_hex2bin($raw_content_hex);
        \sodium_memzero($raw_content_hex);

        if (\mb_strlen($raw_content, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \RuntimeException('Encryption key ' . $key_path . ' must be SODIUM_CRYPTO_SECRETBOX_KEYBYTES long');
        }

        return new self($raw_content);
    }

    public function decrypt(string $ciphertext): string
    {
        $nonce             = \mb_substr($ciphertext, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext_length = \mb_strlen($ciphertext, '8bit');
        $encrypted         = \mb_substr($ciphertext, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, $ciphertext_length, '8bit');

        $plaintext = \sodium_crypto_secretbox_open($encrypted, $nonce, $this->raw_content);
        if ($plaintext === false) {
            throw new \RuntimeException('The ciphertext cannot be decrypted');
        }

        return $plaintext;
    }
}
