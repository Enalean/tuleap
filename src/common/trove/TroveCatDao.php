<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class TroveCatDao extends DataAccessObject //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function getProjectCategories($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT trove_cat.trove_cat_id, fullpath, fullpath_ids
                FROM trove_cat
                  JOIN trove_group_link ON (trove_group_link.trove_cat_id = trove_cat.trove_cat_id)
                WHERE trove_group_link.group_id = $project_id
                ORDER BY trove_cat.fullpath";
        return $this->retrieve($sql);
    }
    public function getMandatoryParentCategoriesUnderRoot()
    {
        $root_id = $this->da->escapeInt(TroveCat::ROOT_ID);

        $sql = "SELECT DISTINCT(parent.trove_cat_id), parent.shortname, parent.fullname
                FROM trove_cat parent
                  LEFT JOIN trove_cat children ON (parent.trove_cat_id = children.parent)
                WHERE parent.mandatory = 1
                  AND parent.parent = $root_id
                  AND children.trove_cat_id IS NOT NULL";

        return $this->retrieve($sql);
    }

    public function getParentCategoriesUnderRoot()
    {
        $root_id = $this->da->escapeInt(TroveCat::ROOT_ID);

        $sql = "SELECT *
                FROM trove_cat
                WHERE root_parent = $root_id";

        return $this->retrieve($sql);
    }

    public function getParentCategoriesUnderRootWithChildren()
    {
        $root_id = $this->da->escapeInt(TroveCat::ROOT_ID);

        $sql = "SELECT DISTINCT(parent.trove_cat_id), parent.shortname, parent.fullname
                FROM trove_cat parent
                  LEFT JOIN trove_cat children ON (parent.trove_cat_id = children.parent)
                WHERE parent.parent = $root_id
                  AND children.trove_cat_id IS NOT NULL";

        return $this->retrieve($sql);
    }

    public function getTopCategories()
    {
        $root_id = $this->da->escapeInt(TroveCat::ROOT_ID);

        $sql = "SELECT trove_cat_id, fullname, mandatory, nb_max_values
                FROM trove_cat
                WHERE parent = $root_id
                ORDER BY fullname";

        return $this->retrieve($sql);
    }

    public function getCategoriesUnderGivenRootForProject($root_id, $project_id)
    {
        $root_id    = $this->da->escapeInt($root_id);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT trove_cat.trove_cat_id, trove_cat.fullpath, trove_cat.fullname, IF(trove_group_link.trove_cat_id IS NULL, 0, 1) as is_selected
                FROM trove_cat LEFT JOIN trove_group_link ON (
                  trove_cat.trove_cat_id = trove_group_link.trove_cat_id
                  AND trove_group_link.group_id = $project_id
                )
                WHERE trove_cat.root_parent = $root_id
                ORDER BY trove_cat.fullname";

        return $this->retrieve($sql);
    }

    public function getCategoryParent($node_id)
    {
        $node_id = $this->da->escapeInt($node_id);

        $sql = "SELECT DISTINCT(parent.trove_cat_id), parent.shortname, parent.fullname, parent.parent AS parent_id
                FROM trove_cat parent
                  LEFT JOIN trove_cat children ON (parent.trove_cat_id = children.parent)
                WHERE parent.trove_cat_id = $node_id
                  ORDER BY children.root_parent, children.fullname";

        return $this->retrieve($sql);
    }

    public function getCategoryChildren($trove_cat_id)
    {
        $trove_cat_id = $this->da->escapeInt($trove_cat_id);

        $sql = "SELECT  children.*, parent.mandatory AS parent_mandatory
                FROM trove_cat children
                LEFT JOIN trove_cat parent ON children.parent = parent.trove_cat_id
                WHERE children.parent = $trove_cat_id
                ORDER BY children.root_parent, children.fullname";

        return $this->retrieve($sql);
    }

    public function getCategoryChildrenToDisplayDuringProjectCreation($trove_cat_id)
    {
        $trove_cat_id = $this->da->escapeInt($trove_cat_id);

        $sql = "SELECT trove_cat_id, shortname, fullname
                FROM trove_cat
                WHERE parent = $trove_cat_id
                AND display_during_project_creation = 1";

        return $this->retrieve($sql);
    }

    public function getMandatoryCategorySelectForAllProject($parent_category_id)
    {
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

    public function updateTroveCat(
        $shortname,
        $fullname,
        $description,
        $parent,
        $newroot,
        $mandatory,
        $display,
        $trove_cat_id,
        $fullpath,
        $fullpath_ids,
        $nb_max_values,
        $is_project_flag
    ) {
        $shortname    = $this->da->quoteSmart($shortname);
        $fullname     = $this->da->quoteSmart($fullname);
        $description  = $this->da->quoteSmart($description);
        $parent       = $this->da->escapeInt($parent);
        $newroot      = $this->da->escapeInt($newroot);
        $mandatory    = $this->da->escapeInt($mandatory);
        $display      = $this->da->escapeInt($display);
        $trove_cat_id = $this->da->escapeInt($trove_cat_id);
        $fullpath     = $this->da->quoteSmart($fullpath);
        $fullpath_ids = $this->da->quoteSmart($fullpath_ids);
        $nb_max_values = $this->da->escapeInt($nb_max_values);
        $is_project_flag = $is_project_flag ? 1 : 0;

        $version = date("Ymd", time()) . '01';

        $sql = "UPDATE trove_cat SET
              shortname = $shortname,
              fullname = $fullname,
              description = $description,
              parent = $parent,
              version = $version,
              root_parent = $newroot,
              mandatory = $mandatory,
              display_during_project_creation = $display,
              fullpath = $fullpath,
              fullpath_ids = $fullpath_ids,
              nb_max_values = $nb_max_values,
              is_project_flag = $is_project_flag
           WHERE trove_cat_id= $trove_cat_id";

        return $this->update($sql);
    }

    public function add(
        $shortname,
        $fullname,
        $description,
        $parent,
        $root_parent,
        $mandatory,
        $display,
        $fullpath,
        $fullpath_ids,
        $nb_max_values,
        $is_project_flag
    ) {
        $escaped_shortname    = $this->da->quoteSmart($shortname);
        $escaped_fullname     = $this->da->quoteSmart($fullname);
        $escaped_description  = $this->da->quoteSmart($description);
        $escaped_parent       = $this->da->escapeInt($parent);
        $escaped_root_parent  = $this->da->escapeInt($root_parent);
        $escaped_mandatory    = $this->da->escapeInt($mandatory);
        $escaped_display      = $this->da->escapeInt($display);
        $escaped_fullpath     = $this->da->quoteSmart($fullpath);
        $escaped_fullpath_ids = $this->da->quoteSmart($fullpath_ids);
        $escaped_nb_max_values = $this->da->escapeInt($nb_max_values);

        $is_project_flag = $is_project_flag ? 1 : 0;

        $version = date("Ymd", time()) . '01';

        $this->startTransaction();

        $sql = "INSERT INTO trove_cat (
                  shortname,
                  fullname,
                  description,
                  parent,
                  version,
                  root_parent,
                  mandatory,
                  display_during_project_creation,
                  fullpath,
                  fullpath_ids,
                  nb_max_values,
                  is_project_flag
              ) values (
                  $escaped_shortname,
                  $escaped_fullname,
                  $escaped_description,
                  $escaped_parent,
                  $version,
                  $escaped_root_parent,
                  $escaped_mandatory,
                  $escaped_display,
                  $escaped_fullpath,
                  $escaped_fullpath_ids,
                  $escaped_nb_max_values,
                  $is_project_flag
              )";

        $trove_cat_id  = $this->updateAndGetLastId($sql);
        $fullpath_ids .= $trove_cat_id;

        $this->updateTroveCat(
            $shortname,
            $fullname,
            $description,
            $parent,
            $root_parent,
            $mandatory,
            $display,
            $trove_cat_id,
            $fullpath,
            $fullpath_ids,
            $nb_max_values,
            $is_project_flag
        );

        $this->commit();
    }

    public function delete($trove_cat_id)
    {
        $trove_cat_id = $this->da->escapeInt($trove_cat_id);

        $sql = "DELETE trove_cat, trove_group_link
                FROM trove_cat
                LEFT JOIN trove_group_link ON trove_cat.trove_cat_id = trove_group_link.trove_cat_id
                WHERE trove_cat.trove_cat_id = $trove_cat_id";

        return $this->update($sql);
    }

    public function removeProjectTopCategoryValue($project_id, $trove_cat_root_id)
    {
        $project_id        = $this->da->escapeInt($project_id);
        $trove_cat_root_id = $this->da->escapeInt($trove_cat_root_id);

        $sql = "DELETE FROM trove_group_link
                WHERE group_id = $project_id
                AND trove_cat_root= $trove_cat_root_id";

        $this->update($sql);
    }
}
