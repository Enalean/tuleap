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

class FRSProcessorDao extends DataAccessObject
{

    public function listProcessors($group_id)
    {
        $sql = sprintf(
            "SELECT * FROM frs_processor WHERE group_id=100 OR group_id=%s ORDER BY rank",
            $this->da->quoteSmart((int) $group_id)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return int processor_id or null
     */
    public function searchProcessorId($group_id, $name)
    {
        $sql = sprintf(
            "SELECT * FROM frs_processor WHERE (group_id=100 OR group_id=%s) AND name=%s ORDER BY rank",
            $this->da->escapeInt($group_id),
            $this->da->quoteSmart((string) $name)
        );
        $proc = $this->retrieve($sql);
        if (!$proc->valid()) {
                   return null;
        }
        $current = $proc->current();
        return $current['processor_id'];
    }

    public function searchById($processor_id)
    {
        $processor_id = $this->da->escapeInt($processor_id);
        $sql = "SELECT * FROM frs_processor WHERE processor_id=$processor_id";

        return $this->retrieveFirstRow($sql);
    }
}
