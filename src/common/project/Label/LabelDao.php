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

namespace Tuleap\Project\Label;

use DataAccessObject;

class LabelDao extends DataAccessObject
{
    public function searchLabelsLikeKeywordByProjectId($project_id, $keyword, $limit, $offset)
    {
        $project_id = $this->da->escapeInt($project_id);
        $keyword    = $this->da->quoteLikeValueSurround($keyword);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM project_label
                WHERE project_id = $project_id
                  AND name LIKE $keyword
                ORDER BY id
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }
}
