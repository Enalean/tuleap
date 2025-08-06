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
use Tuleap\Cryptography\SymmetricLegacy2025\EncryptionKey;

class KeyFactory
{
    /**
     * @throws CannotPerformIOOperationException
     */
    public function getEncryptionKey(): EncryptionKey
    {
        $encryption_key_file_path = (new SecretKeyFileOnFileSystem())->initAndGetEncryptionKeyPath();
        $file_data                = \file_get_contents($encryption_key_file_path);
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
}
