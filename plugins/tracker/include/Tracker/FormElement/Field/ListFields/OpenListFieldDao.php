<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields;

use DataAccessObject;

class OpenListFieldDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_openlist';
    }

    public function searchChangesetValues(
        $changeset_id,
        $field_id,
        $bindtable_select,
        $bindtable_select_nb,
        $bindtable_from,
        $bindtable_join_on_id,
    ) {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $field_id     = $this->da->escapeInt($field_id);
        //      SELECT user.user_id AS id, user.user_name, user.realname, CONCAT(user.realname,' (',user.user_name,')') AS full_name, null as openvalue_label, l.insertion_order
        //      FROM user
        //          INNER JOIN tracker_changeset_value_openlist AS l ON (l.bindvalue_id = user.user_id)
        //          INNER JOIN tracker_changeset_value AS c ON ( l.changeset_value_id = c.id AND c.changeset_id = $changeset_id AND c.field_id = $field_id )
        //      UNION
        $openvalue_select = '';
        for ($i = 0; $i < $bindtable_select_nb; ++$i) {
            $openvalue_select .= ' null,';
        }
        $sql = "SELECT $bindtable_join_on_id as id, $bindtable_select, null as openvalue_is_hidden, null as openvalue_label, l.insertion_order
                FROM $bindtable_from
                    INNER JOIN tracker_changeset_value_openlist AS l ON (l.bindvalue_id = $bindtable_join_on_id)
                    INNER JOIN tracker_changeset_value AS c ON ( l.changeset_value_id = c.id AND c.changeset_id = $changeset_id AND c.field_id = $field_id )
                UNION
                SELECT ov.id AS id, $openvalue_select ov.is_hidden as openvalue_is_hidden, ov.label as openvalue_label, l.insertion_order
                FROM tracker_field_openlist_value AS ov
                    INNER JOIN tracker_changeset_value_openlist AS l ON (l.openvalue_id = ov.id)
                    INNER JOIN tracker_changeset_value AS c ON ( l.changeset_value_id = c.id AND c.changeset_id = $changeset_id AND c.field_id = $field_id )
                ORDER BY insertion_order";

        return $this->retrieve($sql);
    }
}
