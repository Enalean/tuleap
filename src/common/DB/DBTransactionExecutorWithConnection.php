<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\DB;

final class DBTransactionExecutorWithConnection implements DBTransactionExecutor
{
    /**
     * @var DBConnection
     */
    private $db_connection;

    public function __construct(DBConnection $db_connection)
    {
        $this->db_connection = $db_connection;
    }

    /**
     * @throws \Throwable
     * @return mixed
     */
    public function execute(callable $atomic_operations)
    {
        return $this->db_connection->getDB()->tryFlatTransaction($atomic_operations);
    }
}
