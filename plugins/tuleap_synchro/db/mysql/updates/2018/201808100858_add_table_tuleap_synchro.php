<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

class b201808100858_add_table_tuleap_synchro extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add table to store informations to launch automatically update between two Tuleaps\' instance';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_tuleap_synchro_endpoint (
                username_source text NOT NULL,
                password_source VARCHAR(255) NOT NULL,
                project_source VARCHAR(30) NOT NULL,
                tracker_source INT(11) NOT NULL,
                username_target text NOT NULL,
                project_target VARCHAR(30) NOT NULL,
                base_uri VARCHAR(100) NOT NULL,
                webhook VARCHAR(17) NOT NULL PRIMARY KEY
                ) ENGINE=InnoDB;";

        $this->db->createTable('plugin_tuleap_synchro_endpoint', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_tuleap_synchro_endpoint')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_tuleap_synchro_endpoint table is missing');
        }
    }
}
