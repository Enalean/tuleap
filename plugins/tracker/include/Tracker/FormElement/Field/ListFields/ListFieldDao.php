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

namespace Tuleap\Tracker\FormElement\Field\ListFields;

use Tuleap\Tracker\FormElement\SpecificPropertiesDao;

class ListFieldDao extends SpecificPropertiesDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_list';
    }

    public function searchByFieldId($field_id)
    {
        $field_id = $this->da->escapeInt($field_id);
        $sql      = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id ";
        return $this->retrieve($sql);
    }

    public function save($field_id, $bind_type)
    {
        $field_id  = $this->da->escapeInt($field_id);
        $bind_type = $this->da->quoteSmart($bind_type);
        $sql       = "REPLACE INTO $this->table_name (field_id, bind_type)
                VALUES ($field_id, $bind_type)";
        return $this->update($sql);
    }
}
