<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201612231324_update_trove_categories_incorrect_values extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Update incorrect values of trove_categories';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();

        $update_statement = $this->db->dbh->prepare(
            'UPDATE trove_cat SET root_parent = ?, fullpath = ?, fullpath_ids = ? WHERE trove_cat_id = ?'
        );

        $incorrectly_set_trove_categories = $this->retrieveIncorrectlySetTroveCategories();
        foreach ($incorrectly_set_trove_categories as $incorrectly_set_trove_category) {
            $trove_category_id = $incorrectly_set_trove_category['trove_cat_id'];

            list($root_parent, $fullpath, $fullpath_ids) = $this->computePaths(
                $trove_category_id,
                $incorrectly_set_trove_category['fullname'],
                $incorrectly_set_trove_category['fullpath_ids']
            );

            $has_been_executed = $update_statement->execute(
                array(
                    $root_parent,
                    $fullpath,
                    $fullpath_ids,
                    $trove_category_id
                )
            );
            if ($has_been_executed === false) {
                $this->rollBackOnError('An error occurred while updating parent trove categories');
            }
        }

        $this->db->dbh->commit();
    }

    /**
     * @return array
     */
    private function retrieveIncorrectlySetTroveCategories()
    {
        $sql = "SELECT trove_cat_id, parent, root_parent, fullname, fullpath_ids
                FROM trove_cat
                WHERE root_parent = 0 AND (fullpath_ids = '' OR parent <> 0)";

        return $this->db->dbh->query($sql)->fetchAll();
    }

    /**
     * @return array
     */
    private function computePaths($trove_cat_id, $trove_cat_fullname, $existing_fullpath_ids)
    {
        $root_parent  = 0;
        $fullpath     = '';
        $fullpath_ids = '';

        $statement              = $this->db->dbh->prepare('SELECT fullname FROM trove_cat WHERE trove_cat_id = ?');
        $hierarchy_trove_cat_id = explode(' :: ', $existing_fullpath_ids);
        foreach ($hierarchy_trove_cat_id as $parent_trove_cat_id) {
            if ($parent_trove_cat_id === $trove_cat_id) {
                continue;
            }

            if ($root_parent === 0) {
                $root_parent = $parent_trove_cat_id;
            }

            if (! $statement->execute(array($parent_trove_cat_id))) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                    'Could not retrieve information on trove categories hierarchy'
                );
            }

            $trove_cat_information = $statement->fetch();

            $fullpath     .= $trove_cat_information['fullname'] . ' :: ';
            $fullpath_ids .= $parent_trove_cat_id . ' :: ';
        }

        $fullpath     .= $trove_cat_fullname;
        $fullpath_ids .= $trove_cat_id;

        return array($root_parent, $fullpath, $fullpath_ids);
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
