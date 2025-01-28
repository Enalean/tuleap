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
 */

namespace Tuleap\Tracker\FormElement\Field\StaticField\RichText;

use Tuleap\Tracker\FormElement\SpecificPropertiesDao;

class RichTextFieldDao extends SpecificPropertiesDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_staticfield_richtext';
    }

    public function save($field_id, $row)
    {
        $field_id = $this->da->escapeInt($field_id);

        if (isset($row['static_value'])) {
            $static_value = $this->da->quoteSmart($row['static_value']);
        } else {
            return false;
        }

        $sql = "REPLACE INTO $this->table_name (field_id, static_value)
                VALUES ($field_id, $static_value)";
        return $this->retrieve($sql);
    }

    public function duplicate(int $from_field_id, int $to_field_id): bool
    {
        $from_field_id = $this->da->escapeInt($from_field_id);
        $to_field_id   = $this->da->escapeInt($to_field_id);

        $sql = "REPLACE INTO $this->table_name (field_id, static_value)
                SELECT $to_field_id, static_value
                FROM $this->table_name
                WHERE field_id = $from_field_id";
        return $this->update($sql);
    }
}
