<?php
/**
 * Copyright (c) Enalean SAS 2016. All rights reserved
 *
 * This file is a part of Tuleap.
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

namespace Tuleap\Tracker\DAO;

use Tuleap\Tracker\FormElement\Field\FloatingPointNumber\FloatValueDao;

class ComputedDao extends FloatValueDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_computedfield_manual_value';
    }

    public function getManuallySetValueForChangeset($changeset_id, $field_id)
    {
        $field_id     = $this->da->escapeInt($field_id);
        $changeset_id = $this->da->escapeInt($changeset_id);

        $sql = "SELECT value
                FROM  tracker_changeset_value_computedfield_manual_value AS manual_value
                INNER JOIN tracker_changeset_value AS changeset_value
                    ON changeset_value.id = manual_value.changeset_value_id
                WHERE changeset_value.changeset_id = $changeset_id
                AND field_id = $field_id";

        return $this->retrieveFirstRow($sql);
    }

    public function create($changeset_value_id, $value)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $value              = $this->da->escapeFloat($value);
        $sql = "REPLACE INTO tracker_changeset_value_computedfield_manual_value(changeset_value_id, value)
                VALUES ($changeset_value_id, $value)";

        return $this->update($sql);
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql = "INSERT INTO tracker_changeset_value_computedfield_manual_value(changeset_value_id, value)
                SELECT $to, value
                FROM tracker_changeset_value_computedfield_manual_value
                WHERE changeset_value_id = $from";

        return $this->update($sql);
    }
}
