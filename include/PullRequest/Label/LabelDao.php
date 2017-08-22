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

namespace Tuleap\PullRequest\Label;

use DataAccessObject;

class LabelDao extends DataAccessObject
{
    public function searchLabelByPullRequestId($pull_request_id, $limit, $offset)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $limit           = $this->da->escapeInt($limit);
        $offset          = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS project_label.*
                FROM plugin_pullrequest_label
                    INNER JOIN project_label ON (
                        plugin_pullrequest_label.label_id = project_label.id
                        AND plugin_pullrequest_label.pull_request_id = $pull_request_id
                    )
                ORDER BY project_label.id
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }
}
