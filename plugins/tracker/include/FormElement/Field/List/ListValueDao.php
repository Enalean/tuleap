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

namespace Tuleap\Tracker\FormElement\Field\List;

use Tuleap\Tracker\FormElement\Field\FieldValueDao;

class ListValueDao extends FieldValueDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_list';
    }

    public function create($changeset_value_id, $value_ids)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $values             = [];
        if (! is_array($value_ids)) {
            $value_ids = [$value_ids];
        }
        $nb_values = count($value_ids);
        foreach ($value_ids as $v) {
            $v = $this->da->escapeInt($v);
            if (! $v) {
                $v = 100; //None value
            }
            // if $nb_values > 1 && $v == 100, then skip the None value.
            // if there are others value (None + value = value)
            if ($nb_values <= 1 || $v != 100) {
                $values[] = "($changeset_value_id, $v)";
            }
        }
        if ($values) {
            $values = implode(',', $values);
            $sql    = "INSERT INTO $this->table_name(changeset_value_id, bindvalue_id)
                    VALUES $values";
            return $this->update($sql);
        }
        return true;
    }

    public function createNoneValue($tracker_id, $field_id)
    {
        $changeset_value_ids = $this->createNoneChangesetValue($tracker_id, $field_id);
        if ($changeset_value_ids === false) {
            return false;
        }
        $sql = "INSERT INTO $this->table_name(changeset_value_id, bindvalue_id)
                    VALUES ( " . implode(', 100 ),( ', array_map(fn (int $value): string => $this->da->escapeInt($value), $changeset_value_ids)) . ', 100 )';
        return $this->update($sql);
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql  = "INSERT INTO $this->table_name(changeset_value_id, bindvalue_id)
                SELECT $to, bindvalue_id
                FROM $this->table_name
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }
}
