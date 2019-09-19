<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class b20150421_add_hostname_info_to_mirror extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add a hostname column in plugin_git_mirrors table
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_git_mirrors ADD hostname VARCHAR(255) NULL";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column hostname to the table plugin_git_mirrors');
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('plugin_git_mirrors', 'hostname')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column hostname in table plugin_git_mirrors is missing');
        }
    }
}
