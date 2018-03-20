<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use Tuleap\DB\DataAccessObject;

class Git_Driver_Gerrit_ProjectCreatorStatusDao extends DataAccessObject
{
    public function getSystemEventForRepository($repository_id)
    {
        $parameters = $this->getDB()->escapeLikeValue($repository_id . '::') . '%';

        $sql = 'SELECT status, UNIX_TIMESTAMP(create_date) create_date, log
                FROM system_event
                WHERE type = ?
                AND parameters LIKE ?
                ORDER BY id DESC
                LIMIT 1';

        $row = $this->getDB()->row($sql, SystemEvent_GIT_GERRIT_MIGRATION::NAME, $parameters);

        if (empty($row)) {
            return null;
        }

        return $row;
    }
}
