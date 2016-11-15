<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class TroveCatDao extends DataAccessObject {

    public function getMandatoryParentCategoriesUnderRoot() {
        $root_id = $this->da->escapeInt(TroveCat::ROOT_ID);

        $sql = "SELECT DISTINCT(parent.trove_cat_id), parent.shortname, parent.fullname
                FROM trove_cat parent
                  LEFT JOIN trove_cat children ON (parent.trove_cat_id = children.parent)
                WHERE parent.mandatory = 1
                  AND parent.parent = $root_id
                  AND children.trove_cat_id IS NOT NULL";

        return $this->retrieve($sql);
    }

    public function getCategoryChildren($trove_cat_id) {
        $trove_cat_id = $this->da->escapeInt($trove_cat_id);

        $sql = "SELECT trove_cat_id, shortname, fullname, description, parent
                FROM trove_cat
                WHERE parent = $trove_cat_id
                ORDER BY fullname";

        return $this->retrieve($sql);
    }

    public function getCategoryChildrenToDisplayDuringProjectCreation($trove_cat_id) {
        $trove_cat_id = $this->da->escapeInt($trove_cat_id);

        $sql = "SELECT trove_cat_id, shortname, fullname
                FROM trove_cat
                WHERE parent = $trove_cat_id
                AND display_during_project_creation = 1";


        return $this->retrieve($sql);
    }

    public function getMandatoryCategorySelectForAllProject($parent_category_id) {
        $parent_category_id = $this->da->escapeInt($parent_category_id);

        $sql = "SELECT groups.group_id, trove_cat.fullname AS result
                FROM groups
                    LEFT JOIN trove_group_link ON (
                        trove_group_link.group_id = groups.group_id
                        AND trove_group_link.trove_cat_root = $parent_category_id
                    )
                    LEFT JOIN trove_cat ON (trove_cat.trove_cat_id = trove_group_link.trove_cat_id)
                GROUP BY groups.group_id";

        return $this->retrieve($sql);
    }
}