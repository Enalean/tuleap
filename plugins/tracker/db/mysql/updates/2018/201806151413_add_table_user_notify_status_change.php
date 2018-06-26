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

class b201806151413_add_table_user_notify_status_change extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add table tracker_only_status_change_notification_subscribers';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS tracker_only_status_change_notification_subscribers (
                  tracker_id INT(11) NOT NULL,
                  user_id INT(11) NOT NULL,
                  PRIMARY KEY (tracker_id, user_id)
                ) ENGINE=InnoDB;';

        $this->db->createTable('tracker_only_status_change_notification_subscribers', $sql);
    }
}
