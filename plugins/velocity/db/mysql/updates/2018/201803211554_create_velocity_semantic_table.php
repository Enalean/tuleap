<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

class b201803211554_create_velocity_semantic_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add table plugin_velocity_semantic_field to store velocity semantic field';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_velocity_semantic_field (
          tracker_id int(11) NOT NULL,
          field_id int(11) unsigned NOT NULL,
          PRIMARY KEY (tracker_id, field_id)
        ) ENGINE=InnoDB';

        $this->db->createTable('plugin_velocity_semantic_field', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_velocity_semantic_field')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('plugin_velocity_semantic_field table is missing');
        }
    }
}
