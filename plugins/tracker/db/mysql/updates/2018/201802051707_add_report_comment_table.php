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

class b201802051707_add_report_comment_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add table tracker_report_criteria_comment_value to store comment content saved in report';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE tracker_report_criteria_comment_value(
                    report_id INT(11) NOT NULL PRIMARY KEY,
                    comment VARCHAR(255)
                ) ENGINE=InnoDB';

        $this->db->createTable('tracker_report_criteria_comment_value', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('tracker_report_criteria_comment_value')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('tracker_report_criteria_comment_value table is missing');
        }
    }
}
