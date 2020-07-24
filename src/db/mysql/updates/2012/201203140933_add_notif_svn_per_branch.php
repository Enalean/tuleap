<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

class b201203140933_add_notif_svn_per_branch extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
Create a new dedicated table for svn paths and header to ease the management of notification per branchs.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE svn_notification (
                    group_id int(11) NOT NULL,
                    svn_events_mailing_list text NOT NULL DEFAULT "",
                    path varchar(255) DEFAULT "/",
                    PRIMARY KEY (group_id, path)
                )';
        $this->db->createTable('svn_notification', $sql);

        $sql = 'INSERT INTO svn_notification (group_id, svn_events_mailing_list)
                           SELECT group_id, svn_events_mailing_list 
                           FROM groups 
                           WHERE svn_events_mailing_list <>"" 
               ';
        if ($this->db->tableNameExists('groups') && $this->db->tableNameExists('svn_notification')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured copying from  table groups to table svn_notification');
            }
        }

        $sql = 'ALTER TABLE groups DROP COLUMN svn_events_mailing_list';
        if ($this->db->tableNameExists('groups') && $this->db->columnNameExists('groups', 'svn_events_mailing_list')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while deleting svn_events_mailing_list column from groups table');
            }
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('svn_notification')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('svn_notification table is missing');
        }
        if ($this->db->columnNameExists('groups', 'svn_events_mailing_list')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('svn_events_mailing_list column is not deleted from groups table');
        }
    }
}
