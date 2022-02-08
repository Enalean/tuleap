<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook;

use DataAccessObject;

class HookDao extends DataAccessObject
{
    public function delete($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "DELETE FROM plugin_hudson_git_server WHERE repository_id = $repository_id";

        return $this->update($sql);
    }

    public function save($id, $jenkins_server, bool $is_commit_reference_needed)
    {
        $id                         = $this->da->escapeInt($id);
        $jenkins_server             = $this->da->quoteSmart($jenkins_server);
        $is_commit_reference_needed = $this->da->escapeInt($is_commit_reference_needed ? 1 : 0);

        $sql = "REPLACE INTO plugin_hudson_git_server(repository_id, jenkins_server_url, is_commit_reference_needed)
                VALUES($id, $jenkins_server, $is_commit_reference_needed)";

        return $this->update($sql);
    }

    public function searchById($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT jenkins_server_url, is_commit_reference_needed
                FROM plugin_hudson_git_server
                WHERE repository_id = $id";
        return $this->retrieve($sql);
    }
}
