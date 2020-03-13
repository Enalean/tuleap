<?php
/**
* Copyright Enalean (c) 2015. All rights reserved.
*
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

class b201504081124_create_plugin_statistics_configuration_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Creating plugin_statistics_configuration table
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_statistics_configuration (
                    daily_purge_is_activated TINYINT(1) NOT NULL
                ) ENGINE = InnoDB";

        $this->db->createTable('plugin_statistics_configuration', $sql);

        $sql = "INSERT INTO plugin_statistics_configuration (daily_purge_is_activated) VALUE (0)";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding a value in table plugin_statistics_configuration: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_statistics_configuration')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_statistics_configuration table is missing');
        }
    }
}
