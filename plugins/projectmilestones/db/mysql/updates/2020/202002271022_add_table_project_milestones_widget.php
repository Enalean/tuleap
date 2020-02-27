<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types=1);

final class b202002271022_add_table_project_milestones_widget extends ForgeUpgrade_Bucket // @phpcs:ignore
{
    public function description(): string
    {
        return 'Add table to store project\'s id of each projectMilestone widgets';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = "CREATE TABLE plugin_projectmilestones_widget (
                id INT(11) AUTO_INCREMENT NOT NULL PRIMARY KEY,
                group_id INT(11)
                ) ENGINE=InnoDB;";

        $this->db->createTable('plugin_projectmilestones_widget', $sql);

        $this->db->dbh->beginTransaction();

        $sql    = 'SELECT project_dashboards.project_id as project_id, dashboards_lines_columns_widgets.id as id
                    FROM dashboards_lines
                    INNER JOIN dashboards_lines_columns
                    ON (dashboards_lines.id = dashboards_lines_columns.line_id)
                    INNER JOIN dashboards_lines_columns_widgets
                    ON (dashboards_lines_columns.id = dashboards_lines_columns_widgets.column_id)
                    INNER JOIN project_dashboards
                    ON (project_dashboards.id = dashboards_lines.dashboard_id)
                    WHERE dashboards_lines_columns_widgets.name = "milestone"';
        $result = $this->db->dbh->query($sql);

        if ($result === false) {
            $this->rollBackOnError("An error occured while trying to select milestone widgets.");
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
            $project_id = $widget['project_id'];

            $this->db->dbh->query(("INSERT INTO plugin_projectmilestones_widget(id, group_id) VALUES (null, $project_id)"));

            $new_content = $this->db->dbh->lastInsertId();

            if ($new_content === false) {
                $this->rollBackOnError("An error occured while trying to insert new project milestone widgets.");
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
