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

namespace Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact;

use Tuleap\Tracker\FormElement\Field\FieldValueDao;

class PermissionsOnArtifactFieldValueDao extends FieldValueDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_permissionsonartifact';
    }

    /**
     * @return \DataAccessResult|false
     */
    public function searchById($changeset_value_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $sql                = "SELECT changeset_value_id, use_perm, ugroup.ugroup_id, ugroup.name AS ugroup_name
                FROM tracker_changeset_value_permissionsonartifact
                JOIN ugroup ON (ugroup.ugroup_id = tracker_changeset_value_permissionsonartifact.ugroup_id)
                WHERE changeset_value_id = $changeset_value_id ";
        return $this->retrieve($sql);
    }

    public function create($changeset_value_id, $use_perm, $value_ids)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $use_perm           = $this->da->escapeInt($use_perm);
        $values             = [];
        if (! is_array($value_ids)) {
            $value_ids = [$value_ids];
        }
        foreach ($value_ids as $v) {
            $values[] = "($changeset_value_id, $use_perm, $v)";
        }
        if ($values) {
            $values = implode(',', $values);
            $sql    = "INSERT INTO $this->table_name(changeset_value_id, use_perm, ugroup_id)
                    VALUES $values";
            return $this->update($sql);
        }
        return true;
    }

    public function createNoneValue($tracker_id, $field_id)
    {
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $changeset_value_ids = $this->createNoneChangesetValue((int) $tracker_id, $field_id);
        if ($changeset_value_ids === false) {
            return false;
        }
        $sql = " INSERT INTO $this->table_name(changeset_value_id, use_perm, ugroup_id) VALUES (" . implode(', 1, 1), ( ', array_map(fn (int $value): string => $this->da->escapeInt($value), $changeset_value_ids)) . ", 1, 1) ";
        $this->update($sql);
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql  = "INSERT INTO $this->table_name(changeset_value_id, use_perm, ugroup_id)
                SELECT $to, use_perm, ugroup_id
                FROM $this->table_name
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }
}
