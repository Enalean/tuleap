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

namespace Tuleap\Tracker\FormElement\Field\Date;

use Tuleap\Tracker\FormElement\SpecificPropertiesDao;

class DateFieldDao extends SpecificPropertiesDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_date';
    }

    public function save($field_id, $row)
    {
        $field_id = $this->da->escapeInt($field_id);

        if (isset($row['default_value'])) {
            if (is_numeric($row['default_value'])) {
                $default_value = $this->da->escapeInt($row['default_value']);
            } else {
                $default_value = $this->da->escapeInt(strtotime($row['default_value']));
            }
        } else {
            $default_value = 'NULL';
        }

        if (isset($row['default_value_type'])) {
            $default_value_type = $this->da->escapeInt($row['default_value_type']);
        } else {
            $default_value_type = isset($row['default_value']) ? 1 : 0;
        }

        $display_time = (int) (isset($row['display_time']) && $row['display_time']);

        $sql = "REPLACE INTO $this->table_name (field_id, default_value, default_value_type, display_time)
                VALUES ($field_id, $default_value, $default_value_type, $display_time)";

        return $this->update($sql);
    }
}
