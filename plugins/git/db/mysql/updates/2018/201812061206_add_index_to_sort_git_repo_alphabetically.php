<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class b201812061206_add_index_to_sort_git_repo_alphabetically extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add index to sort git repositories alphabetically';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->dropIndex("plugin_git", "project_id");
        $this->db->addIndex(
            "plugin_git",
            "idx_project_repository",
            "ALTER TABLE plugin_git ADD INDEX idx_project_repository(project_id, repository_id)"
        );
        $this->db->addIndex(
            "plugin_git_log",
            "idx_repository_date",
            "ALTER TABLE plugin_git_log ADD INDEX idx_repository_date(repository_id, push_date)"
        );
    }

    /**
     * @param string $table
     * @param string $index
     */
    private function dropIndex($table, $index)
    {
        $this->log->info("Remove index $index from $table");
        if ($this->db->indexNameExists($table, $index)) {
            $sql = "ALTER TABLE `$table` DROP INDEX `$index`";
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                $info = $this->db->dbh->errorInfo();
                $msg  = 'An error occured adding index to ' . $table . ': ' . $info[2] . ' (' . $info[1] . ' - ' . $info[0] . ')';
                $this->log->error($msg);
                throw new ForgeUpgrade_Bucket_Db_Exception($msg);
            }
            $this->log->info("$index successfully removed");
        } else {
            $this->log->info("$index does not exist");
        }
    }
}
