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

class b201603171433_add_icon_color_columns extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add icon and color columns to plugin_openidconnectclient_provider table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_openidconnectclient_provider
                ADD COLUMN icon VARCHAR(50) NULL,
                ADD COLUMN color VARCHAR(20) NULL";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while adding icon and color columns ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('plugin_openidconnectclient_provider', 'icon')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while adding icon column to the plugin_openidconnectclient_provider table');
        }

        if (! $this->db->columnNameExists('plugin_openidconnectclient_provider', 'color')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while adding color column to the plugin_openidconnectclient_provider table');
        }
    }
}
