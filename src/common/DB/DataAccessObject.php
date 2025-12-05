<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DB;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\KeyFactoryFromFileSystem;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

abstract class DataAccessObject
{
    private readonly DBConnection $db_connection;
    public readonly DatabaseUUIDFactory $uuid_factory;
    private readonly KeyFactory $encryption_key_factory;

    public function __construct(
        ?DBConnection $db_connection = null,
        ?DatabaseUUIDFactory $database_uuid_factory = null,
        ?KeyFactory $encryption_key_factory = null,
    ) {
        $this->db_connection          = $db_connection ?? DBFactory::getMainTuleapDBConnection();
        $this->uuid_factory           = $database_uuid_factory ?? new DatabaseUUIDV7Factory();
        $this->encryption_key_factory = $encryption_key_factory ?? new KeyFactoryFromFileSystem();
    }

    final protected function getDB(): EasyDB
    {
        return $this->db_connection->getDB();
    }

    final protected function getDBTransactionExecutor(): DBTransactionExecutor
    {
        return new DBTransactionExecutorWithConnection($this->db_connection);
    }

    /**
     * Returns the number of affected rows by the LAST query.
     * Must be called immediately after performing a query.
     */
    public function foundRows(): int
    {
        return (int) $this->getDB()->single('SELECT FOUND_ROWS()');
    }

    final protected function encryptDataToStoreInATableRow(
        ConcealedString $data_to_encrypt,
        EncryptionAdditionalData $encryption_additional_data,
    ): string {
        $key = $this->encryption_key_factory->getEncryptionKey();

        return SymmetricCrypto::encrypt(
            $data_to_encrypt,
            $encryption_additional_data,
            $key,
        );
    }

    final protected function decryptDataStoredInATableRow(
        #[\SensitiveParameter]
        string $encrypted_data,
        EncryptionAdditionalData $encryption_additional_data,
    ): ConcealedString {
        $reflection_concealed_string = new \ReflectionClass(ConcealedString::class);
        return $reflection_concealed_string->newLazyProxy(
            function () use ($encrypted_data, $encryption_additional_data): ConcealedString {
                $key = $this->encryption_key_factory->getEncryptionKey();

                return new ConcealedString(
                    SymmetricCrypto::decrypt($encrypted_data, $encryption_additional_data, $key)->getString()
                );
            }
        );
    }
}
