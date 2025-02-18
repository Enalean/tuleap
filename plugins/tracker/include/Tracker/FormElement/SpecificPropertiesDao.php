<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use DataAccessObject;

abstract class SpecificPropertiesDao extends DataAccessObject
{
    public function searchByFieldId($field_id)
    {
        $field_id = $this->da->escapeInt($field_id);
        $sql      = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id ";
        return $this->retrieve($sql);
    }

    public function delete($field_id)
    {
        $field_id = $this->da->escapeInt($field_id);
        $sql      = "DELETE FROM $this->table_name
                WHERE field_id = $field_id ";
        return $this->retrieve($sql);
    }

    abstract public function save($field_id, $row);
}
