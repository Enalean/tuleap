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

class b201610241145_update_frs_admin_description extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Update frs_admin group description';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $frs_admin_description = 'FRS Admin group includes users who are File Release System administrator';

        $this->db->dbh->beginTransaction();

        $sql = "UPDATE ugroup
                SET description = '$frs_admin_description'
                WHERE name = 'FRS_Admin' AND description = 'FRS Admin'";

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError('An error occured while migrating admin frs permissions for ugroup' . $sql);
        }

        $this->db->dbh->commit();
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
