<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\TrackerEncryption\Dao;

use Tracker_FormElement_Field_ValueDao;

class ValueDao extends Tracker_FormElement_Field_ValueDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_encrypted';
    }

    public function create($changeset_value_id, $encrypted_value)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $encrypted_value    = $this->da->quoteSmart($encrypted_value);

        $sql = "REPLACE INTO tracker_changeset_value_encrypted(changeset_value_id, value)
                VALUES ($changeset_value_id, $encrypted_value)";

        return $this->update($sql);
    }

    public function createNoneValue($tracker_id, $field_id)
    {
        $changeset_value_ids = $this->createNoneChangesetValue($tracker_id, $field_id);
        if ($changeset_value_ids === false) {
            return false;
        }

        $changeset_value_ids = $this->da->escapeIntImplode($changeset_value_ids);
        $sql = " INSERT INTO tracker_changeset_value_encrypted (changeset_value_id, value)
                 VALUES ( '$changeset_value_ids', '')";

        return $this->update($sql);
    }
}
