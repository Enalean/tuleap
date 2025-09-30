<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class b201804091400_add_plugin_tracker_artifacts_deleted_table  extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add table plugin_tracker_artifacts_deleted to store user id and timestamp of deleted artifacts';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_tracker_deleted_artifacts(
                    timestamp int(11) NOT NULL,
                    user_id INT(11) NOT NULL,
                    nb_artifacts_deleted int(2) NOT NULL,
                    PRIMARY KEY (timestamp, user_id)
                ) ENGINE=InnoDB
        ';

        $this->db->createTable('plugin_tracker_deleted_artifacts', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_tracker_deleted_artifacts')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('plugin_tracker_deleted_artifacts table is missing');
        }
    }
}
