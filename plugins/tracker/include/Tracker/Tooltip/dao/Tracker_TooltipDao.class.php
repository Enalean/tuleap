<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  Data Access Object for Tracker_Tooltip
 */
class Tracker_TooltipDao extends DataAccessObject implements Tracker_Semantic_IRetrieveSemanticDARByTracker
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_tooltip';
    }

    public function searchByTrackerId($tracker_id)
    {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                ORDER BY rank";
        return $this->retrieve($sql);
    }

    public function add($tracker_id, $field_id, $rank)
    {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $field_id    = $this->da->escapeInt($field_id);
        $rank        = $this->da->escapeInt($this->prepareRanking('tracker_tooltip', 0, $tracker_id, $rank, 'field_id', 'tracker_id'));
        $sql = "REPLACE INTO $this->table_name(tracker_id, field_id, rank)
                VALUES ($tracker_id, $field_id, $rank)";
        return $this->update($sql);
    }

    public function remove($tracker_id, $field_id)
    {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $field_id    = $this->da->escapeInt($field_id);
        $sql = "DELETE FROM $this->table_name
                WHERE tracker_id = $tracker_id AND field_id = $field_id";
        return $this->update($sql);
    }
}
