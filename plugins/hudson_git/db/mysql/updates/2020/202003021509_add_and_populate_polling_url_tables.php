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

class b202003021509_add_and_populate_polling_url_tables extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
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
        $this->createRepositoryTable();
        $this->createProjectTable();
        $this->populateRepositoryTable();
        $this->populateProjectTable();
        $this->dropOldColumnForRepository();
        $this->dropOldColumnForProject();
        $this->db->dbh->commit();
    }

    private function createRepositoryTable(): void
    {
        $sql = "
            CREATE TABLE plugin_hudson_git_job_polling_url (
               job_id  int(11) UNSIGNED NOT NULL PRIMARY KEY,
               job_url text NOT NULL
            ) ENGINE=InnoDB;
        ";

        $this->db->createTable('plugin_hudson_git_job_polling_url', $sql);
    }

    private function createProjectTable(): void
    {
        $sql = "
            CREATE TABLE plugin_hudson_git_project_server_job_polling_url (
               job_id int(11) UNSIGNED NOT NULL PRIMARY KEY,
               job_url text NOT NULL
            ) ENGINE=InnoDB;
        ";

        $this->db->createTable('plugin_hudson_git_project_server_job_polling_url', $sql);
    }

    private function populateRepositoryTable(): void
    {
        $sql = "
            INSERT INTO plugin_hudson_git_job_polling_url (job_id, job_url)
            SELECT id, job_url
            FROM plugin_hudson_git_job;
        ";

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError(
                "An error occured while populating plugin_hudson_git_job_polling_url table."
            );
        }
    }

    private function populateProjectTable(): void
    {
        $sql = "
            INSERT INTO plugin_hudson_git_project_server_job_polling_url (job_id, job_url)
            SELECT id, job_url
            FROM plugin_hudson_git_project_server_job;
        ";

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError(
                "An error occured while populating plugin_hudson_git_project_server_job_polling_url table."
            );
        }
    }

    private function dropOldColumnForProject(): void
    {
        $sql = "ALTER TABLE plugin_hudson_git_project_server_job DROP COLUMN job_url;";

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError(
                "An error occured while removing job_url from plugin_hudson_git_project_server_job table."
            );
        }
    }

    private function dropOldColumnForRepository(): void
    {
        $sql = "ALTER TABLE plugin_hudson_git_job DROP COLUMN job_url;";

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError(
                "An error occured while removing job_url from plugin_hudson_git_job table."
            );
        }
    }

    private function rollBackOnError($message): void
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
