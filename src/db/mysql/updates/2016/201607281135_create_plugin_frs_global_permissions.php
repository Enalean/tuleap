<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class b201607281135_create_plugin_frs_global_permissions extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Create table plugin_frs_global_permissions for frs';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS frs_global_permissions(
                    project_id int(11) NOT NULL,
                    permission_type VARCHAR(255) NOT NULL,
                    ugroup_id int(11)
                )";

        $res = $this->db->dbh->exec($sql);

        if (! $this->db->tableNameExists('frs_global_permissions')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'frs_global_permissions'
            );
        }
    }
}
