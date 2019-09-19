<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

class FRSFileTypeDao extends DataAccessObject
{

    public function listFileTypes($group_id)
    {
        return $this->retrieve("SELECT * FROM frs_filetype ORDER BY type_id");
    }

    /**
     * @return int type_id or null
     */
    public function searchTypeId($name)
    {
        $sql = sprintf(
            "SELECT type_id FROM frs_filetype WHERE name=%s ORDER BY type_id LIMIT 1",
            $this->da->quoteSmart((string) $name)
        );
        $filetype = $this->retrieve($sql);
        if (!$filetype->valid()) {
            return null;
        }
        $current = $filetype->current();
        return $current['type_id'];
    }

    public function searchById($id)
    {
        $id = $this->da->escapeInt($id);
        $sql = "SELECT * FROM frs_filetype WHERE type_id=$id ORDER BY type_id LIMIT 1";
        return $this->retrieveFirstRow($sql);
    }
}
