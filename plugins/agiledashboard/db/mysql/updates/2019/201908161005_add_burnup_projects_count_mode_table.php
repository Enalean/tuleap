<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
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

class b201908161005_add_burnup_projects_count_mode_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return "Add burnup projects in count mode table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "
            CREATE TABLE plugin_agiledashboard_burnup_projects_count_mode (
              project_id  INT(11) NOT NULL PRIMARY KEY
            ) ENGINE=InnoDB;
        ";

        $this->db->createTable('plugin_agiledashboard_burnup_projects_count_mode', $sql);
    }
}
