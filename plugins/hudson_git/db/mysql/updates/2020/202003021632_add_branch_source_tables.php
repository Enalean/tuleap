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

class b202003021632_add_branch_source_tables extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return "Add branch sources tables";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createRepositoryTable();
        $this->createProjectTable();
    }

    private function createRepositoryTable(): void
    {
        $sql = "
            CREATE TABLE plugin_hudson_git_job_branch_source (
               job_id  int(11) UNSIGNED NOT NULL PRIMARY KEY,
               status_code INT(4) UNSIGNED NOT NULL
            ) ENGINE=InnoDB;
        ";

        $this->db->createTable('plugin_hudson_git_job_branch_source', $sql);
    }

    private function createProjectTable(): void
    {
        $sql = "
            CREATE TABLE plugin_hudson_git_project_server_job_branch_source (
                job_id  int(11) UNSIGNED NOT NULL PRIMARY KEY,
                status_code INT(4) UNSIGNED NOT NULL
            ) ENGINE=InnoDB;
        ";

        $this->db->createTable('plugin_hudson_git_project_server_job_branch_source', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_hudson_git_job_branch_source')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'the table plugin_hudson_git_job_branch_source is missing'
            );
        }

        if (! $this->db->tableNameExists('plugin_hudson_git_project_server_job_branch_source')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'the table plugin_hudson_git_project_server_job_branch_source is missing'
            );
        }
    }
}
