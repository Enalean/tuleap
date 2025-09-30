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

class b201806261500_create_tracker_webhook_log_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Create plugin_tracker_webhook_log table to store webhook logs.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_tracker_webhook_log (
                created_on int(11) NOT NULL,
                webhook_id int(11) unsigned NOT NULL,
                status TEXT NOT NULL,
                INDEX idx(webhook_id)
            )';

        $result = $this->db->createTable('plugin_tracker_webhook_log', $sql);

        if ($result === false || ! $this->db->tableNameExists('plugin_tracker_webhook_log')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Creation of table plugin_tracker_webhook_log failed');
        }
    }
}
