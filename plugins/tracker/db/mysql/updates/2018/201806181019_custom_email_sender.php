<?php
/**
 * Copyright (c) Ericsson AB, 2018. All Rights Reserved.
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

class b201806181019_custom_email_sender  extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Create table plugin_tracker_notification_email_custom_sender_format';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createFormatTable();
    }

    private function createFormatTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_tracker_notification_email_custom_sender_format(
                    tracker_id int(11) NOT NULL,
                    format text,
                    enabled bool,
                    PRIMARY KEY (tracker_id),
                    FOREIGN KEY (tracker_id)
                        REFERENCES tracker(id)
                )";

        $res = $this->db->createTable('plugin_tracker_notification_email_custom_sender_format', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_tracker_notification_email_custom_sender_format')) {
            throw new ForgeUpgrate_Bucket_Exception_UpgradeNotCompleteException('plugin_tracker_notification_email_custom_sender_format table is missing');
        }
    }
}
