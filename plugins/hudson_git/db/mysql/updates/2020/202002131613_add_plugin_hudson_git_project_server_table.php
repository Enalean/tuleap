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

class b202002131613_add_plugin_hudson_git_project_server_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return "Add plugin_hudson_git_project_server table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "
            CREATE TABLE plugin_hudson_git_project_server(
               project_id int(11) NOT NULL,
               jenkins_server_url varchar(255) default '',
               PRIMARY KEY (project_id, jenkins_server_url)
            ) ENGINE=InnoDB;
        ";

        $this->db->createTable('plugin_hudson_git_project_server', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_hudson_git_project_server')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'the table plugin_hudson_git_project_server is missing'
            );
        }
    }
}
