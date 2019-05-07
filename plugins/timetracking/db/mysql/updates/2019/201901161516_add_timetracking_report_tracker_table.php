<?php
/**
 * Copyright Enalean (c) 2019. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

class b201901161516_add_timetracking_report_tracker_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'add timetracking report tracker table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_timetracking_overview_report_tracker (
              report_id INT(11) NOT NULL,
              tracker_id INT(11) NOT NULL,
              PRIMARY KEY (report_id, tracker_id))ENGINE=InnoDB';

        $this->db->createTable('plugin_timetracking_overview_report_tracker', $sql);
    }
}
