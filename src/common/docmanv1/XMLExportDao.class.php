<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class DocmanV1_XMLExportDao extends DataAccessObject
{

    public function searchAllNonEmptyGroups($group_id)
    {
        return $this->retrieve(
            "SELECT doc_groups.* FROM doc_groups
                JOIN doc_data ON doc_groups.doc_group = doc_data.doc_group
            WHERE group_id = " . $this->da->escapeInt($group_id) . "
            GROUP BY doc_group
            ORDER BY group_rank"
        );
    }

    public function searchAllDocs($doc_group_id)
    {
        return $this->retrieve("SELECT * FROM doc_data WHERE doc_group = " . $this->da->escapeInt($doc_group_id) . ' ORDER BY rank');
    }

    public function searchUGroupForObjectPermission($permission_type, $object_id)
    {
        return $this->retrieve(
            "SELECT ugroup.ugroup_id AS id, ugroup.name
             FROM permissions
               JOIN ugroup ON (ugroup.ugroup_id = permissions.ugroup_id)
             WHERE permission_type = " . $this->da->quoteSmart($permission_type) . "
               AND object_id = " . $this->da->escapeInt($object_id)
        );
    }
}
