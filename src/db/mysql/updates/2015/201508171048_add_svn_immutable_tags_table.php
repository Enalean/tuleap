<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

class b201508171048_add_svn_immutable_tags_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return "Modify table svn_immutable_tags_whitelist to store SVN immutable tags paths";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();
        $this->createTable();
        $this->convertExistingConfig();
        $this->convertWhitelist();
        $this->deleteOldTable();
        $this->db->dbh->commit();
    }

    private function createTable()
    {
        $sql = "CREATE TABLE svn_immutable_tags (
                group_id INT(11),
                paths TEXT NOT NULL DEFAULT '',
                whitelist TEXT NOT NULL DEFAULT '',
                PRIMARY KEY(group_id)
        )";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $this->rollBackOnError('An error occured while adding svn_immutable_tags table.');
        }
    }

    private function convertExistingConfig()
    {
        $sql = "INSERT INTO svn_immutable_tags (group_id, paths)
                SELECT groups.group_id, '/*/tags'
                FROM groups
                WHERE groups.status = 'A'
                  AND groups.svn_commit_to_tag_denied = 1";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $this->rollBackOnError('An error occured while converting config 1.');
        }

        $sql = "INSERT INTO svn_immutable_tags (group_id, paths)
                SELECT groups.group_id, '/tags'
                FROM groups
                WHERE groups.status = 'A'
                  AND groups.svn_commit_to_tag_denied = 2";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $this->rollBackOnError('An error occured while converting config 2.');
        }
    }

    private function convertWhitelist()
    {
        $sql = "SELECT *
                FROM svn_immutable_tags_whitelist";

        foreach ($this->db->dbh->query($sql)->fetchAll() as $row) {
            $new_content = $this->getNewContent($row['content']);
            $group_id    = $row['group_id'];

            $sql = "UPDATE svn_immutable_tags
                    SET whitelist = '$new_content'
                    WHERE group_id = $group_id";

            $insert = $this->db->dbh->exec($sql);
            if ($insert === false) {
                $this->rollBackOnError("An error occured while converting whitelist for project $group_id.");
            }
        }
    }

    private function getNewContent($content)
    {
        $folders = explode(PHP_EOL, $content);

        foreach ($folders as $index => $folder) {
            $folders[$index] = '/tags/' . $folder;
        }

        return implode(PHP_EOL, $folders);
    }

    private function deleteOldTable()
    {
        $sql = "DROP TABLE svn_immutable_tags_whitelist";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            $this->rollBackOnError("An error occured while trying to remove the old svn_immutable_tags_whitelist table.");
        }
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
