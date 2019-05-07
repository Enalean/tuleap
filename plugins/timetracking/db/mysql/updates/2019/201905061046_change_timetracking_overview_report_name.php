<?php
/**
 * Copyright Enalean (c) 2019 - present. All rights reserved.
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

class b201905061046_change_timetracking_overview_report_name extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Change name for plugin_timetracking_overview_report to plugin_timetracking_overview_widget';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }
    public function up()
    {
        $sql = "RENAME TABLE plugin_timetracking_overview_report TO plugin_timetracking_overview_widget";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Changing name did not work.');
        }
    }
}
