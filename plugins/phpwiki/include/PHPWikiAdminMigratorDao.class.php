<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class PHPWikiAdminMigratorDao extends DataAccessObject {

    /**
     * @return DataAccessResult|false
     */
    public function searchProjectsUsingPlugin() {
        $project_status = Project::STATUS_ACTIVE;
        $sql = "SELECT groups.* FROM groups
                JOIN service ON groups.group_id = service.group_id
                WHERE is_active = 1 AND is_used = 1 AND short_name = 'plugin_phpwiki' AND status = '$project_status'";
        return $this->retrieve($sql);
    }

    /**
     * @return bool
     */
    public function canMigrate($project_id) {
        $project_id   = $this->getDa()->escapeInt($project_id);
        $sql          = "SELECT COUNT(DISTINCT groups.group_id) as count FROM groups
                         JOIN service ON groups.group_id = service.group_id
                         JOIN wiki_page ON groups.group_id = wiki_page.group_id
                         JOIN wiki_nonempty ON wiki_page.id = wiki_nonempty.id
                         WHERE is_active = 1 AND is_used = 1 AND short_name = 'wiki' AND
                         groups.group_id NOT IN (SELECT group_id FROM plugin_phpwiki_page) AND
                         groups.group_id = $project_id";
        $result_count = $this->retrieve($sql);
        if ($result_count) {
            $row = $result_count->getRow();
            return $row['count'] === '1';
        }
        return false;
    }
}