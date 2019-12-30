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

declare(strict_types=1);

namespace Tuleap\User\AccessKey;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

class LastAccessKeyIdentifierStore
{
    public const STORAGE_NAME = 'last_access_key_identifier';
    /**
     * @var SplitTokenFormatter
     */
    private $split_token_formatter;
    /**
     * @var EncryptionKey
     */
    private $encryption_key;
    /**
     * @var array
     */
    private $storage;

    public function __construct(SplitTokenFormatter $split_token_formatter, EncryptionKey $encryption_key, array &$storage)
    {
        $this->split_token_formatter = $split_token_formatter;
        $this->encryption_key        = $encryption_key;
        $this->storage               =& $storage;
    }

    public function storeLastGeneratedAccessKeyIdentifier(SplitToken $key): void
    {
        $this->storage[self::STORAGE_NAME] = SymmetricCrypto::encrypt(
            $this->split_token_formatter->getIdentifier($key),
            $this->encryption_key
        );
    }

    /**
     * @throws \Tuleap\Cryptography\Exception\InvalidCiphertextException
     */
    public function getLastGeneratedAccessKeyIdentifier(): ?ConcealedString
    {
        if (! isset($this->storage[self::STORAGE_NAME])) {
            return null;
        }

        $identifier = SymmetricCrypto::decrypt($this->storage[self::STORAGE_NAME], $this->encryption_key);
        unset($this->storage[self::STORAGE_NAME]);
        return $identifier;
    }
}
