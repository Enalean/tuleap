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

class b201808171523_add_table_source_artifact_id extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add table plugin_tracker_source_artifact_id to match between an existing artifact and his source artifact id. Used during an update import.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_tracker_source_artifact_id (
                artifact_id INT(11) NOT NULL,
                source_artifact_id INT(11) NOT NULL,
                source_platform VARCHAR(100) NOT NULL,
                PRIMARY KEY (artifact_id),
                INDEX (source_platform)
                ); ENGINE=InnoDB;";

        $this->db->createTable('plugin_tracker_source_artifact_id', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_tracker_source_artifact_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_tracker_source_artifact_id table is missing');
        }
    }
}
