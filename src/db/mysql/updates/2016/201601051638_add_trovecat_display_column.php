<?php
/**
 * Copyright (c) Enalean SAS 2016. All rights reserved
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

class b201601051638_add_trovecat_display_column extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Add display_during_project_creation attribute for trovecat";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();
        $this->alterTable();
        $this->convertExistingCategories();
        $this->db->dbh->commit();
    }

    public function alterTable()
    {
        $sql = "ALTER TABLE trove_cat
                ADD COLUMN display_during_project_creation TINYINT(1) NOT NULL DEFAULT 0";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while modifying the trove_cat table.');
        }
    }

    public function convertExistingCategories()
    {
        $sql = "UPDATE trove_cat parent LEFT JOIN  trove_cat children ON parent.trove_cat_id=children.root_parent
            AND parent.mandatory=1 SET children.display_during_project_creation=1 WHERE parent.mandatory=1";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while updating the trove_cat table values.');
        }
    }
}
