<?php
/**
 * Copyright (c) Enalean SAS 2013 - Present. All rights reserved
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

class b201306110951_add_parent_child_project_link_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return <<<EOT
add parent child project link table
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE project_parent (
                    group_id INT(11) PRIMARY KEY,
                    parent_group_id INT(11) NOT NULL
                )';
        if (! $this->db->tableNameExists('project_parent')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while adding table project_parent');
            }
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('project_parent')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Table project_parent not created');
        }
    }
}
