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
 */

class b201806251409_create_tracker_webhook_url_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Create plugin_tracker_webhook_url table to store webhook URLs.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_tracker_webhook_url (
                id int(11) unsigned PRIMARY KEY AUTO_INCREMENT,
                tracker_id int(11) NOT NULL,
                url TEXT NOT NULL,
                INDEX idx_tracker_webhook_url_tracker_id (tracker_id)
            )';

        $result = $this->db->createTable('plugin_tracker_webhook_url', $sql);

        if ($result === false || ! $this->db->tableNameExists('plugin_tracker_webhook_url')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Creation of table plugin_tracker_webhook_url failed');
        }
    }
}
