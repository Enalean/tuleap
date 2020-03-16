<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

class b201102090815_add_column_repository_events_mailing_prefix extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add the column repository_events_mailing_prefix to set the appropriate prefix used in post-receive email notification.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_git " .
               " ADD `repository_events_mailing_prefix` varchar(64) DEFAULT '[SCM]'";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column repository_events_mailing_prefix to the table plugin_git');
        }
    }

    public function postUp()
    {
        if (!$this->db->columnNameExists('plugin_git', 'repository_events_mailing_prefix')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column repository_events_mailing_prefix in table plugin_git is missing');
        }
    }
}
