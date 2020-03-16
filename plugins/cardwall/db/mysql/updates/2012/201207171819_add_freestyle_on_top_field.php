<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class b201207171819_add_freestyle_on_top_field extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add field to store wether the cardwall use freestyle columns or not
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_cardwall_on_top
                ADD COLUMN use_freestyle_columns tinyint(4) default 0 AFTER tracker_id";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column use_freestyle_columns to plugin_cardwall_on_top: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (!$this->db->columnNameExists('plugin_cardwall_on_top', 'use_freestyle_columns')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('use_freestyle_columns field is missing in plugin_cardwall_on_top table table');
        }
    }
}
