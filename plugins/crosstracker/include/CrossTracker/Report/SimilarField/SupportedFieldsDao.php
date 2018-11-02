<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\SimilarField;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class SupportedFieldsDao extends DataAccessObject
{
    public function searchByTrackerIds(array $tracker_ids)
    {
        $tracker_ids_statement = EasyStatement::open()->in('tracker_field.tracker_id IN (?*)', $tracker_ids);

        $sql = "SELECT tracker_field.tracker_id,
                    tracker_field.id AS field_id,
                    tracker_field.name,
                    tracker_field.formElement_type AS type
                FROM tracker_field
                     JOIN tracker ON (tracker_field.tracker_id = tracker.id)
                     JOIN groups ON (groups.group_id = tracker.group_id)
                WHERE tracker.deletion_date IS NULL
                  AND groups.status = 'A'
                  AND tracker_field.use_it = 1
                  AND $tracker_ids_statement
                  AND tracker_field.formElement_type = 'string'";

        $parameters = $tracker_ids_statement->values();

        return $this->getDB()->safeQuery($sql, $parameters);
    }
}
