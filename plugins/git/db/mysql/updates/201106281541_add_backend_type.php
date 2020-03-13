<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

class b201106281541_add_backend_type extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add repository_backend_type column in order to manage gitolite integration
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (!$this->db->columnNameExists('plugin_git', 'repository_backend_type')) {
            $sql = "ALTER TABLE plugin_git " .
               " ADD `repository_backend_type` varchar(16) DEFAULT 'gitshell'";
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column repository_events_mailing_prefix to the table plugin_git');
            }
        }
    }

    public function postUp()
    {
        if (!$this->db->columnNameExists('plugin_git', 'repository_backend_type')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column repository_backend_type in table plugin_git is missing');
        }
    }
}
