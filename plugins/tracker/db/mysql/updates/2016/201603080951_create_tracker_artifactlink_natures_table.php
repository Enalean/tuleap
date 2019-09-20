<?php
/**
 * Copyright (c) Enalean 2015. All rights reserved
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

class b201603080951_create_tracker_artifactlink_natures_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add plugin_tracker_artifactlink_natures table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_tracker_artifactlink_natures (
                    shortname     VARCHAR(255) NOT NULL PRIMARY KEY,
                    forward_label VARCHAR(255) NOT NULL,
                    reverse_label VARCHAR(255) NOT NULL
                ) ENGINE=InnoDB";

        $this->db->createTable('plugin_tracker_artifactlink_natures', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('plugin_tracker_artifactlink_natures')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('Table plugin_tracker_artifactlink_natures not created');
        }
    }
}
