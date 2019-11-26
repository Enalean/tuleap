<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

class b201911261428_create_involved_notifications_table extends ForgeUpgrade_Bucket //phpcs:ignore
{
    public function description()
    {
        return 'Create plugin_tracker_involved_notification_subscribers table.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_tracker_involved_notification_subscribers (
                   tracker_id INT(11) NOT NULL,
                   user_id INT(11) NOT NULL,
                   PRIMARY KEY (tracker_id, user_id)
                ) ENGINE=InnoDB;';

        $this->db->createTable('plugin_tracker_involved_notification_subscribers', $sql);
    }
}
