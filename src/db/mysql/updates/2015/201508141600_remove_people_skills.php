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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201508141600_remove_people_skills extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Remove people skills feature';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->removeFieldsUserTable();
        $this->dropTablesPeopleSkills();
    }

    private function dropTablesPeopleSkills()
    {
        $sql = 'DROP TABLE people_skill, people_skill_inventory, people_skill_level, people_skill_year';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while deleting people skills tables.');
        }
    }

    private function removeFieldsUserTable()
    {
        $sql = 'ALTER TABLE user DROP COLUMN people_view_skills, DROP COLUMN people_resume';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while editing user table for removing people skills informations.');
        }
    }
}
