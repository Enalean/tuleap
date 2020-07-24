<?php
/**
 * Copyright (c) Enalean SAS 2013. All rights reserved
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

class b201305071724_add_svn_change_log_option extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
Add svn change log option
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE groups ADD COLUMN svn_can_change_log TINYINT(1) NOT NULL default '0' AFTER svn_mandatory_ref";
        if ($this->db->tableNameExists('groups')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column svn_can_change_log to table groups');
            }
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('groups', 'svn_can_change_log')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column owner not created in system_event');
        }
    }
}
