<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\FormElement\Field;

use DataAccessObject;
use Tracker_Artifact_Changeset_ValueDao;

abstract class FieldValueDao extends DataAccessObject
{
    public function searchById($changeset_value_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $sql                = "SELECT *
                FROM $this->table_name
                WHERE changeset_value_id = $changeset_value_id ";
        return $this->retrieve($sql);
    }

    public function delete($changeset_value_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $sql                = "DELETE
                FROM $this->table_name
                WHERE changeset_value_id = $changeset_value_id ";
        return $this->update($sql);
    }

    /**
     * Function that creates a value record for all artifact last changeset
     * @param int $tracker_id
     * @param int $field_id
     * @return mixed array of changeset_value_id or false if nothing inserted
     */
    public function createNoneChangesetValue($tracker_id, $field_id)
    {
        $changesetValueDao   = new Tracker_Artifact_Changeset_ValueDao();
        $changeset_value_ids = $changesetValueDao->createFromLastChangesetByTrackerId($tracker_id, $field_id);
        if (empty($changeset_value_ids)) {
            return false;
        }
        return $changeset_value_ids;
    }
}
