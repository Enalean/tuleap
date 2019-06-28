<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201705121719_update_table_project_dashboards extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Alter table project_dashboards';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE project_dashboards
                  DROP INDEX project_id,
                  ADD INDEX idx(project_id, name(5));
               ";

        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('project_dashboards')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('project_dashboards table is missing');
        }
    }
}
