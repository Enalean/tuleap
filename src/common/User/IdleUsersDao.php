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
 */

declare(strict_types=1);

namespace Tuleap\User;

use ForgeConfig;
use Tuleap\DB\DataAccessObject;

class IdleUsersDao extends DataAccessObject
{
    /**
     * Gets the user_id and last_access_date of idle users
     *
     * @param int $start_date Unix timestamp
     * @param int $end_date Unix timestamp
     *
     * @return array Query result
     */
    public function getIdleAccounts(int $start_date, int $end_date) : array
    {
        $sql  = 'SELECT  user.user_id, last_access_date FROM user ' .
        ' INNER JOIN user_access AS access  ON user.user_id=access.user_id' .
        ' WHERE (user.status != "D" OR user.status != "S") AND ' .
        ' (access.last_access_date != 0 AND access.last_access_date BETWEEN ? AND ?)';
        return $this->getDB()->run($sql, $start_date, $end_date);
    }
}
