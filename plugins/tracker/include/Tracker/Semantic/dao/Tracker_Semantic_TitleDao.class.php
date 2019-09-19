<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


class Tracker_Semantic_TitleDao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_semantic_title';
    }

    public function searchByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    public function save($tracker_id, $field_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id   = $this->da->escapeInt($field_id);
        $sql = "REPLACE INTO $this->table_name (tracker_id, field_id)
                VALUES ($tracker_id, $field_id)";
        return $this->update($sql);
    }

    public function delete($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "DELETE FROM $this->table_name WHERE tracker_id = $tracker_id";

        return $this->update($sql);
    }

    public function getNbOfTrackerWithoutSemanticTitleDefined(array $trackers_id)
    {
        $trackers_id = $this->da->escapeIntImplode($trackers_id);

        $sql = "SELECT count(*) AS nb
                FROM tracker
                    LEFT JOIN tracker_semantic_title AS title
                    ON (tracker.id = title.tracker_id)
                WHERE tracker.id IN ($trackers_id)
                    AND title.tracker_id IS NULL";

        $row = $this->retrieveFirstRow($sql);

        return $row['nb'];
    }
}
