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

namespace Tuleap\Project\Flags;

use Tuleap\DB\DataAccessObject;

class ProjectFlagsDao extends DataAccessObject
{
    public function searchProjectFlags(int $project_id): array
    {
        $sql = 'SELECT trove_cat.fullname as label, trove_cat.description
                FROM trove_group_link
                    INNER JOIN trove_cat AS top_category ON (
                        trove_group_link.trove_cat_root = top_category.trove_cat_id
                        AND top_category.is_project_flag IS TRUE
                    )
                    INNER JOIN trove_cat ON (
                        trove_group_link.trove_cat_id = trove_cat.trove_cat_id
                    )
                WHERE trove_group_link.group_id = ?
                ORDER BY top_category.fullname
                LIMIT 2';

        return $this->getDB()->run($sql, $project_id);
    }
}
