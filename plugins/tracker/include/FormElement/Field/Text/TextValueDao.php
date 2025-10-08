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

namespace Tuleap\Tracker\FormElement\Field\Text;

use Tuleap\Tracker\FormElement\Field\FieldValueDao;

class TextValueDao extends FieldValueDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_text';
    }

    public function create($changeset_value_id, $value)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $value              = $this->da->quoteSmart($value);

        $sql = "INSERT INTO $this->table_name(changeset_value_id, value)
                VALUES ($changeset_value_id, $value)";

        return $this->update($sql);
    }

    public function createWithBodyFormat($changeset_value_id, $value, $body_format)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $value              = $this->da->quoteSmart($value);
        $body_format        = $this->da->quoteSmart($body_format);

        $sql = "INSERT INTO $this->table_name(changeset_value_id, value, body_format)
                VALUES ($changeset_value_id, $value, $body_format)";

        return $this->update($sql);
    }

    /**
     * create none value
     * @param int $tracker_id
     * @param int $field_id
     * @return
     */
    public function createNoneValue($tracker_id, $field_id)
    {
        $changeset_value_ids = $this->createNoneChangesetValue($tracker_id, $field_id);
        if ($changeset_value_ids === false) {
            return false;
        }
        $sql = " INSERT INTO $this->table_name(changeset_value_id, value)
                 VALUES
                  ( " . implode(' , \'\' ),' . "\n" . ' ( ', array_map(fn (int $value): string => $this->da->escapeInt($value), $changeset_value_ids)) . ", '')";
        return $this->update($sql);
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql  = "INSERT INTO $this->table_name(changeset_value_id, value, body_format)
                SELECT $to, value, body_format
                FROM $this->table_name
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }
}
