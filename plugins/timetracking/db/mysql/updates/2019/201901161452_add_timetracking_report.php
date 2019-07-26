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
class b201901161452_add_timetracking_report extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'add timetracking report table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_timetracking_overview_report (
        id INT(11) AUTO_INCREMENT NOT NULL PRIMARY KEY)ENGINE=InnoDB';

        $this->db->createTable('plugin_timetracking_overview_report', $sql);

        $this->db->dbh->beginTransaction();

        $sql    = ' SELECT *
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
            'UPDATE dashboards_lines_columns_widgets
             SET content_id = ?
             WHERE id = ? '
        );

        foreach ($widgets as $widget) {
            $this->db->dbh->query(('INSERT INTO plugin_timetracking_overview_report(id) VALUES (null)'));

            $new_content = $this->db->dbh->lastInsertId();
            if ($new_content === false) {
                $this->rollBackOnError("An error occured while trying to insert new reports.");
            }

            $response = $statement->execute([$new_content, $widget["id"]]);
            if ($response === false) {
                    $this->rollBackOnError("An error occured while trying to updates widgets.");
            }
        }
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
