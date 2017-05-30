<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Trafficlights;

use DataAccessObject;

class ArtifactDao extends DataAccessObject
{

    public function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_artifact';
    }

    public function searchPaginatedByTrackerId($tracker_id, $milestone_id, $limit, $offset, $reverse_order)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $milestone_id = $this->da->escapeInt($milestone_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);
        $order      = ($reverse_order) ? 'DESC' : 'ASC';

        $milestone_filter = $this->milestoneSQLFilter($milestone_id);
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                $milestone_filter
                WHERE A.tracker_id = $tracker_id
                ORDER BY A.id $order
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function searchPaginatedOpenByTrackerId($tracker_id, $milestone_id, $limit, $offset) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $milestone_id = $this->da->escapeInt($milestone_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $milestone_filter = $this->milestoneSQLFilter($milestone_id);
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                $milestone_filter
                WHERE (
                    SS.field_id IS NULL
                    OR
                    CVL2.bindvalue_id = SS.open_value_id
                )
                ORDER BY A.id DESC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function searchPaginatedClosedByTrackerId($tracker_id, $milestone_id, $limit, $offset) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $milestone_id = $this->da->escapeInt($milestone_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $milestone_filter = $this->milestoneSQLFilter($milestone_id);
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS t ON (A.tracker_id = t.id)
                    INNER JOIN tracker_semantic_status AS ss USING(tracker_id)
                    INNER JOIN tracker_changeset_value AS cv ON(cv.field_id = ss.field_id AND A.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_changeset_value_list AS cvl ON(cvl.changeset_value_id = cv.id)
                    INNER JOIN tracker_changeset AS tc ON (tc.artifact_id = A.id)
                    LEFT JOIN tracker_semantic_status AS open_values ON (cvl.bindvalue_id = open_values.open_value_id AND open_values.tracker_id = t.id)
                $milestone_filter
                WHERE open_values.open_value_id IS NULL AND t.id = $tracker_id
                GROUP BY A.id
                ORDER BY A.id DESC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    private function milestoneSQLFilter($milestone_id)
    {
        if ($milestone_id === '0') {
            return '';
        }

        return "INNER JOIN (
                   tracker_field AS milestone_f
               ) ON (milestone_f.tracker_id = A.tracker_id AND milestone_f.formElement_type = 'art_link' AND use_it = 1)
               INNER JOIN (
                   tracker_changeset_value AS milestone_cv
               ) ON (milestone_cv.changeset_id = A.last_changeset_id AND milestone_cv.field_id = milestone_f.id)
               INNER JOIN (
                   tracker_changeset_value_artifactlink AS milestone_artlink
               ) ON (milestone_artlink.changeset_value_id = milestone_cv.id AND milestone_artlink.artifact_id = $milestone_id)";
    }
}
