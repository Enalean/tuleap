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

namespace Tuleap\Cryptography;

use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\Symmetric\EncryptionKey;

class KeyFactory
{
    /**
     * @throws CannotPerformIOOperationException
     */
    public function getEncryptionKey() : EncryptionKey
    {
        $encryption_key_file_path = \ForgeConfig::get('sys_custom_dir') . '/conf/encryption_secret.key';
        if (! \file_exists($encryption_key_file_path)) {
            $encryption_key = $this->generateEncryptionKey();
            $this->saveKeyFile($encryption_key, $encryption_key_file_path);
            return $encryption_key;
        }

        $file_data = \file_get_contents($encryption_key_file_path);
        if ($file_data === false) {
            throw new CannotPerformIOOperationException("Cannot read the encryption key $encryption_key_file_path");
        }

        $file_data_hex = sodium_hex2bin($file_data);
        \sodium_memzero($file_data);

        $encryption_key = new EncryptionKey(
            new ConcealedString($file_data_hex)
        );

        \sodium_memzero($file_data_hex);

        return $encryption_key;
    }

    private function generateEncryptionKey() : EncryptionKey
    {
        $raw_encryption_key = \random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $key_data           = new ConcealedString($raw_encryption_key);
        \sodium_memzero($raw_encryption_key);

        return new EncryptionKey($key_data);
    }

    private function saveKeyFile(Key $key, string $file_path): void
    {
        $is_success = \touch($file_path);
        if (! $is_success) {
            throw new CannotPerformIOOperationException("Cannot create the key file $file_path");
        }
        $is_success = \chmod($file_path, 0600);
        if (! $is_success) {
            \unlink($file_path);
            throw new CannotPerformIOOperationException("Cannot restrict rights of the key file $file_path to u:rw");
        }

        $raw_key_material     = $key->getRawKeyMaterial();
        $raw_key_material_hex = \sodium_bin2hex($raw_key_material);
        \sodium_memzero($raw_key_material);

        $written_size = \file_put_contents(
            $file_path,
            $raw_key_material_hex
        );

        \sodium_memzero($raw_key_material_hex);

        if ($written_size === false) {
            \unlink($file_path);
            throw new CannotPerformIOOperationException("Cannot write to the key file $file_path");
        }
        $is_success = \chmod($file_path, 0400);
        if (! $is_success) {
            \unlink($file_path);
            throw new CannotPerformIOOperationException("Cannot restrict rights of the key file $file_path to u:r");
        }
    }
}
