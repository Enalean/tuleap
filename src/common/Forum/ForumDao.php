<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Forum;

use Tuleap\DB\DataAccessObject;

class ForumDao extends DataAccessObject
{
    /**
     * @return array{"forum_name": string, "is_public": int}|null
     */
    public function searchActiveForum(int $forum_id, int $project_id)
    {
        $sql = 'SELECT *
                FROM forum_group_list
                WHERE group_forum_id = ?
                  AND group_id = ?
                  AND is_public IN (0, 1)';

        return $this->getDB()->row($sql, $forum_id, $project_id);
    }
}
