<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Enalean\LicenseManager\CountDueLicenses;

use PFUser;
use Tuleap\DB\DataAccessObject;

class DueLicencesDao extends DataAccessObject
{
    public function doesUserlogTableExists(): bool
    {
        $result = $this->getDB()->run("SHOW TABLES LIKE 'plugin_userlog_request'");

        return count($result) === 1;
    }

    public function getRealUsers($project_id): array
    {
        $projects_id = "0, $project_id";

        $sql = 'SELECT users.*
            FROM user as users
                LEFT OUTER JOIN plugin_userlog_request AS userlog ON (users.user_id = userlog.user_id)
                WHERE users.status = ?
                AND userlog.group_id NOT IN (?)
            GROUP BY users.user_id';

        return $this->getDB()->run($sql, PFUser::STATUS_ACTIVE, $projects_id);
    }
}
