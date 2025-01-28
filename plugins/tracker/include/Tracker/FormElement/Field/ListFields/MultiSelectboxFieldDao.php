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

class MultiSelectboxFieldDao extends SpecificPropertiesDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_msb';
    }

    public function save($field_id, $row)
    {
        $field_id = $this->da->escapeInt($field_id);

        if (isset($row['size']) && (int) $row['size']) {
            $size = $this->da->escapeInt($row['size']);
        } else {
            $size = 7;
        }

        $sql = "REPLACE INTO $this->table_name (field_id, size)
                VALUES ($field_id, $size)";
        return $this->retrieve($sql);
    }

    public function duplicate(int $from_field_id, int $to_field_id): bool
    {
        $from_field_id = $this->da->escapeInt($from_field_id);
        $to_field_id   = $this->da->escapeInt($to_field_id);

        $sql = "REPLACE INTO $this->table_name (field_id, size)
                SELECT $to_field_id, size
                FROM $this->table_name
                WHERE field_id = $from_field_id";
        return $this->update($sql);
    }
}
