<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

class b201206061045_create_reminder_table extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add the table tracker_reminder in order to send mail reminders based on a date field.
EOT;
    }

    /**
     * Get the API
     *
     * @return void
     */
    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Creation of the table
     *
     * @return Void
     */
    public function up()
    {
        $sql = 'CREATE TABLE tracker_reminder (
                    reminder_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    tracker_id INT(11) NOT NULL,
                    field_id INT(11) NOT NULL,
                    ugroups VARCHAR(255) NOT NULL,
                    notification_type TINYINT(1) DEFAULT 0,
                    distance INT( 11 ) DEFAULT 0,
                    status TINYINT(1) DEFAULT 1,
                    PRIMARY KEY (reminder_id),
                    UNIQUE KEY (tracker_id, field_id, ugroups, notification_type, distance, status)
                )';
        $this->db->createTable('tracker_reminder', $sql);
    }

    /**
     * Verify the table creation
     *
     * @return Void
     */
    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_reminder')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_reminder table is missing');
        }
    }
}
