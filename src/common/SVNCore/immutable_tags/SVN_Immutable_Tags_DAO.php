<?php
/**
 * Copyright (c) Enalean SAS 2015 - Present. All rights reserved
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

class SVN_Immutable_Tags_DAO extends DataAccessObject // phpcs:ignore
{
    public function getImmutableTagsWhitelistForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT whitelist
                FROM svn_immutable_tags
                WHERE group_id = $project_id";

        return $this->retrieve($sql);
    }

    public function getImmutableTagsPathForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT paths
                FROM svn_immutable_tags
                WHERE group_id = $project_id";

        return $this->retrieve($sql);
    }
}
