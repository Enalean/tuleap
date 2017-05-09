<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\TroveCat;

use DataAccess;

class TroveCatLinkDao extends \DataAccessObject
{
    public function __construct(DataAccess $da = null)
    {
        parent::__construct($da);
        $this->enableExceptionsOnError();
    }

    public function searchTroveCatForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT trove_cat.*
                FROM trove_cat, trove_group_link
                WHERE trove_cat.trove_cat_id = trove_group_link.trove_cat_id
                  AND trove_group_link.group_id = $project_id
                ORDER BY trove_cat.fullpath";

        return $this->retrieve($sql);
    }
}
