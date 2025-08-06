<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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


namespace Tuleap\OnlyOffice\DocumentServer;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;

class DocumentServerKeyEncryption
{
    public function __construct(private KeyFactory $key_factory)
    {
    }

    public function encryptValue(ConcealedString $value): string
    {
        return \sodium_bin2base64(
            SymmetricCrypto::encrypt($value, $this->key_factory->getEncryptionKey()),
            SODIUM_BASE64_VARIANT_ORIGINAL
        );
    }

    public function decryptValue(string $value): ConcealedString
    {
        return SymmetricCrypto::decrypt(
            \sodium_base642bin($value, SODIUM_BASE64_VARIANT_ORIGINAL),
            $this->key_factory->getEncryptionKey(),
        );
    }
}
