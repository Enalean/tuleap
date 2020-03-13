<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVN\Repository;

use DataAccessObject;

class HookDao extends DataAccessObject
{
    public function getHookConfig($id_repository)
    {
        $id_repository = $this->da->escapeInt($id_repository);
        $sql           = "SELECT *
                FROM plugin_svn_hook_config
                WHERE repository_id = $id_repository";
        return $this->retrieveFirstRow($sql);
    }

    public function updateHookConfig($id_repository, array $hook_config)
    {
        $id = $this->da->escapeInt($id_repository);

        $update = array();
        $cols   = array();
        $vals   = array();
        foreach ($hook_config as $tablename => $value) {
            $update[] = "$tablename = " . $this->da->quoteSmart((bool) $value);
            $cols[]   = $tablename;
            $vals[]   = $this->da->quoteSmart((bool) $value);
        }

        $sql = "INSERT INTO plugin_svn_hook_config";
        $sql .= " (repository_id, " . implode(", ", $cols) . ")";
        $sql .= " VALUES ($id, " . implode(", ", $vals) . ")";
        $sql .= " ON DUPLICATE KEY UPDATE " . implode(", ", $update) . ";";

        return $this->update($sql);
    }
}
