<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Logs;

class DBWriterPluginDao extends \DataAccessObject
{

    public function searchRepositoriesForProjects(array $project_names)
    {
        $project_names = $this->da->quoteSmartImplode(',', $project_names);
        $sql = "SELECT id as repository_id, unix_group_name as project_name, name as repository_name
          FROM groups g
          INNER JOIN plugin_svn_repositories r ON (r.project_id = g.group_id)
          WHERE
          g.unix_group_name IN ($project_names)";
        return $this->retrieve($sql);
    }

    public function searchAccessPerDay($day)
    {
        $day = $this->da->escapeInt($day);
        $sql = "SELECT repository_id, user_id
                FROM plugin_svn_full_history
                WHERE day = $day";
        return $this->retrieve($sql);
    }

    public function updateAccess($repository_id, $user_id, $day, $nb_read, $nb_write)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $user_id       = $this->da->escapeInt($user_id);
        $day           = $this->da->escapeInt($day);
        $nb_read       = $this->da->escapeInt($nb_read);
        $nb_write      = $this->da->escapeInt($nb_write);
        $sql = "UPDATE plugin_svn_full_history
                SET svn_write_operations = svn_write_operations + $nb_write,
                  svn_read_operations = svn_read_operations + $nb_read
                WHERE repository_id = $repository_id
                AND user_id = $user_id
                AND day = $day";
        return $this->update($sql);
    }

    public function insertAccess($repository_id, $user_id, $day, $nb_read, $nb_write)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $user_id       = $this->da->escapeInt($user_id);
        $day           = $this->da->escapeInt($day);
        $nb_read       = $this->da->escapeInt($nb_read);
        $nb_write      = $this->da->escapeInt($nb_write);
        $sql = "INSERT INTO plugin_svn_full_history (repository_id, user_id, day, svn_write_operations, svn_read_operations)
               VALUES ($repository_id, $user_id, $day, $nb_write, $nb_read)";
        return $this->update($sql);
    }
}
