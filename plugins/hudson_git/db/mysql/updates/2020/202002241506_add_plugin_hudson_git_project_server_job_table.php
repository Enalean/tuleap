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

class b202002241506_add_plugin_hudson_git_project_server_job_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return "Add plugin_hudson_git_project_server_job table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "
            CREATE TABLE plugin_hudson_git_project_server_job (
               id int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
               project_server_id int(11) UNSIGNED NOT NULL,
               repository_id int(10) UNSIGNED NOT NULL,
               push_date int(11) UNSIGNED NOT NULL,
               job_url text NOT NULL
            ) ENGINE=InnoDB;
        ";

        $this->db->createTable('plugin_hudson_git_project_server_job', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_hudson_git_project_server_job')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'the table plugin_hudson_git_project_server_job is missing'
            );
        }
    }
}
