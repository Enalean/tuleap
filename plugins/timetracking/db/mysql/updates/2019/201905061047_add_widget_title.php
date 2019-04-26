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

class b201905061047_add_widget_title extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add title for plugin_timetracking_overview_report';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }
    public function up()
    {
        $sql = "ALTER TABLE plugin_timetracking_overview_widget ADD widget_title VARCHAR(255) NOT NULL";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Adding column did not work.');
        }

        $this->db->dbh->beginTransaction();

        $sql    = 'SELECT *
                 FROM dashboards_lines_columns_widgets
                 WHERE name = "timetracking-overview"';
        $result = $this->db->dbh->query($sql);

        if ($result === false) {
            $this->rollBackOnError("An error occured while trying to select reports.");
        }

        $this->updateExistingWidget($result);
        $this->db->dbh->commit();
    }

    private function updateExistingWidget($widgets)
    {
        $statement = $this->db->dbh->prepare(
            'UPDATE plugin_timetracking_overview_widget
             SET widget_title = ?
             WHERE id = ?'
        );

        foreach ($widgets as $widget) {
            $response = $statement->execute(["Timetracking Overview", $widget["content_id"]]);
            if ($response === false) {
                $this->rollBackOnError("An error occured while trying to update widgets.");
            }
        }
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
