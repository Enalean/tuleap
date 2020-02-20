<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
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

declare(strict_types=1);

class b202002171529_add_id_in_plugin_hudson_git_project_server_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return "Add id in plugin_hudson_git_project_server table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();
        $this->dropPrimaryKey();
        $this->addIdColumn();
        $this->db->dbh->commit();
    }

    private function addIdColumn(): void
    {
        $sql_alter_table = "ALTER TABLE plugin_hudson_git_project_server
                            ADD COLUMN id int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";

        if ($this->db->dbh->exec($sql_alter_table) === false) {
            $this->rollBackOnError(
                "An error occured while add id column in the plugin_hudson_git_project_server table."
            );
        }
    }

    private function dropPrimaryKey(): void
    {
        $sql_alter_table = "ALTER TABLE plugin_hudson_git_project_server DROP PRIMARY KEY";

        if ($this->db->dbh->exec($sql_alter_table) === false) {
            $this->rollBackOnError(
                "An error occured while removing the primary of plugin_hudson_git_project_server table."
            );
        }
    }

    private function rollBackOnError($message): void
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
