<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

class b201711161420_create_semantic_done_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Create plugin_agiledashboard_semantic_done table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_agiledashboard_semantic_done (
                  tracker_id INT(11) NOT NULL,
                  value_id INT(11) NOT NULL,
                  PRIMARY KEY(tracker_id, value_id),
                  INDEX semantic_done_tracker_idx(tracker_id)
                ) ENGINE=InnoDB";

        $this->db->createTable('plugin_agiledashboard_semantic_done', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_agiledashboard_semantic_done')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'plugin_agiledashboard_semantic_done table is missing'
            );
        }
    }
}
