<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class b201607061503_add_replication_password_for_git_remote_server extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return <<<EOT
Add replication password for git remote server.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Creation of the column
     *
     * @return void
     */
    public function up()
    {
        if (! $this->db->columnNameExists('plugin_git_remote_servers', 'replication_password')) {
            $sql = 'ALTER TABLE plugin_git_remote_servers ADD replication_password TEXT';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occurred while adding the column replication_password to the table plugin_git_remote_servers');
            }
        }
    }

    /**
     * Verify the column creation
     *
     * @return void
     */
    public function postUp()
    {
        if (! $this->db->columnNameExists('plugin_git_remote_servers', 'replication_password')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Column replication_password in table plugin_git_remote_servers is missing');
        }
    }
}
