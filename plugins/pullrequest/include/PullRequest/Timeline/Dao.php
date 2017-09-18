<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\Timeline;

use DataAccessObject;

class Dao extends DataAccessObject
{

    public function searchAllByPullRequestId($pull_request_id)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_pullrequest_timeline_event
                WHERE pull_request_id = $pull_request_id";

        return $this->retrieve($sql);
    }

    public function save($pull_request_id, $user_id, $post_date, $type)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $user_id         = $this->da->escapeInt($user_id);
        $post_date       = $this->da->escapeInt($post_date);
        $type            = $this->da->escapeInt($type);

        $sql = "INSERT INTO plugin_pullrequest_timeline_event (pull_request_id, user_id, post_date, type)
                VALUES ($pull_request_id, $user_id, $post_date, $type)";

        return $this->updateAndGetLastId($sql);
    }


}
