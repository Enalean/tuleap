<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Core\RSS\Project;

use ParagonIE\EasyDB\EasyStatement;

class LatestProjectDao extends \Tuleap\DB\DataAccessObject
{
    public function getLast10NewProjects()
    {
        $statement = EasyStatement::open()->in('?*', [\Project::ACCESS_PUBLIC, \Project::ACCESS_PUBLIC_UNRESTRICTED]);

        $sql = "SELECT group_id
                  FROM groups
                    WHERE access IN ($statement)
                    AND status='A'
                    AND type=1
                  ORDER BY register_time DESC
                  LIMIT 10";
        return $this->getDB()->safeQuery($sql, $statement->values());
    }
}
