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

namespace Tuleap\User\AccessKey;

use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

class LastAccessKeyIdentifierStore
{
    const STORAGE_NAME = 'last_access_key_identifier';
    /**
     * @var EncryptionKey
     */
    private $encryption_key;
    /**
     * @var array
     */
    private $storage;

    public function __construct(EncryptionKey $encryption_key, array &$storage)
    {
        $this->encryption_key = $encryption_key;
        $this->storage        =& $storage;
    }

    public function storeLastGeneratedAccessKeyIdentifier(AccessKey $key)
    {
        $this->storage[self::STORAGE_NAME] = SymmetricCrypto::encrypt($key->getIdentifier(), $this->encryption_key);
    }

    /**
     * @return null|\Tuleap\Cryptography\ConcealedString
     * @throws \Tuleap\Cryptography\Exception\InvalidCiphertextException
     */
    public function getLastGeneratedAccessKeyIdentifier()
    {
        if (! isset($this->storage[self::STORAGE_NAME])) {
            return null;
        }

        $identifier = SymmetricCrypto::decrypt($this->storage[self::STORAGE_NAME], $this->encryption_key);
        unset($this->storage[self::STORAGE_NAME]);
        return $identifier;
    }
}
