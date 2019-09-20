<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Tracker_Migration_V3_SemanticDao extends DataAccessObject
{

    public function create($tv5_id)
    {
        $tv5_id = $this->da->escapeInt($tv5_id);
        $this->createTitle($tv5_id);
        $this->createStatus($tv5_id);
        $this->createContributor($tv5_id);
        $this->createTooltip($tv5_id);
    }

    private function createTitle($tv5_id)
    {
        $sql = "INSERT INTO tracker_semantic_title (tracker_id, field_id)
                SELECT tracker_id, id
                FROM tracker_field
                WHERE name = 'summary'
                  AND tracker_id = $tv5_id";
        return $this->update($sql);
    }

    private function createStatus($tv5_id)
    {
        //open = 1
        $sql = "INSERT INTO tracker_semantic_status(tracker_id, field_id, open_value_id)
                SELECT tracker_id, f.id, v.id
                FROM tracker_field AS f INNER JOIN tracker_field_list_bind_static_value as v ON (f.id = v.field_id)
                WHERE name = 'status_id'
                  AND (v.old_id = 1 OR v.label = 'Open' OR v.label = 'Ouvert')
                  AND f.tracker_id = $tv5_id";
        return $this->update($sql);
    }

    private function createContributor($tv5_id)
    {
        $sql = "INSERT INTO tracker_semantic_contributor (tracker_id, field_id)
                SELECT tracker_id, id
                FROM tracker_field
                WHERE use_it = 1 AND (name = 'assigned_to' OR name = 'multi_assigned_to')
                  AND tracker_id = $tv5_id
                LIMIT 1";
        return $this->update($sql);
    }

    private function createTooltip($tv5_id)
    {
        $sql = "INSERT INTO tracker_tooltip(tracker_id, field_id, rank)
                SELECT tracker_id, id, 1
                FROM tracker_field
                WHERE name = 'summary'
                  AND use_it = 1
                  AND tracker_id = $tv5_id";
        $this->update($sql);

        $sql = "INSERT INTO tracker_tooltip(tracker_id, field_id, rank)
                SELECT tracker_id, id, 2
                FROM tracker_field
                WHERE name = 'status_id'
                  AND use_it = 1
                  AND tracker_id = $tv5_id";
        $this->update($sql);

        $sql = "INSERT INTO tracker_tooltip(tracker_id, field_id, rank)
                SELECT tracker_id, id, 3
                FROM tracker_field
                WHERE name = 'details'
                  AND use_it = 1
                  AND tracker_id = $tv5_id";
        $this->update($sql);
    }
}
