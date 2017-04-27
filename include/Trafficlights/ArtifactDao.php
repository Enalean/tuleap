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

    public function searchPaginatedOpenByTrackerId($tracker_id, $limit, $offset) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*, A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                WHERE (
                        SS.field_id IS NULL
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                ORDER BY A.id DESC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function searchPaginatedClosedByTrackerId($tracker_id, $limit, $offset) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);
        $sql = "SELECT SQL_CALC_FOUND_ROWS a.*, a.*
                FROM tracker_artifact AS a
                    INNER JOIN tracker AS t ON (a.tracker_id = t.id)
                    INNER JOIN tracker_semantic_status AS ss USING(tracker_id)
                    INNER JOIN tracker_changeset_value AS cv ON(cv.field_id = ss.field_id AND a.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_changeset_value_list AS cvl ON(cvl.changeset_value_id = cv.id)
                    INNER JOIN tracker_changeset AS tc ON (tc.artifact_id = a.id)
                    LEFT JOIN tracker_semantic_status AS open_values ON (cvl.bindvalue_id = open_values.open_value_id AND open_values.tracker_id = t.id)
                WHERE open_values.open_value_id IS NULL
                    AND t.id = $tracker_id
                GROUP BY id
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }
}
