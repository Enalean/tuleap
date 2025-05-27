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

abstract class DataAccessObject
{
    private readonly DBConnection $db_connection;
    public readonly DatabaseUUIDFactory $uuid_factory;

    public function __construct(
        ?DBConnection $db_connection = null,
        ?DatabaseUUIDFactory $database_uuid_factory = null,
    ) {
        $this->db_connection = $db_connection ?? DBFactory::getMainTuleapDBConnection();
        $this->uuid_factory  = $database_uuid_factory ?? new DatabaseUUIDV7Factory();
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
}
