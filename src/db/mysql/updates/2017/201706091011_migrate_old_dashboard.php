<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201706091011_migrate_old_dashboard extends ForgeUpgrade_Bucket
{
    public const DASHBOARD_NAME         = 'Dashboard';
    public const OLD_USER_OWNER_TYPE    = 'u';
    public const OLD_PROJECT_OWNER_TYPE = 'g';
    public const NEW_USER_OWNER_TYPE    = 'user';
    public const NEW_PROJECT_OWNER_TYPE = 'project';

    public function description()
    {
        return 'Migrate existing dashboard to new';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->log->warn('Dashboard widget migration might take a while, please be patient...');

        $this->db->dbh->beginTransaction();
        $this->migrateDashboard();
        $this->db->dbh->commit();
    }

    private function migrateDashboard()
    {
        $sql        = "SELECT * FROM owner_layouts";
        $dashboards = $this->db->dbh->query($sql)->fetchAll();

        foreach ($dashboards as $dashboard) {
            $this->log->info('Migrating old dashboard ' . $dashboard['owner_type'] . ' for ' . $dashboard['owner_id']);

            $type = $dashboard['owner_type'];
            $sql  = $this->getInsertStatement($type);
            if (! $sql) {
                continue;
            }

            $insert_stm = $this->db->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $result     = $insert_stm->execute(
                [':owner_id' => $dashboard['owner_id'], ':name' => self::DASHBOARD_NAME]
            );

            if ($result === false) {
                $this->rollBackOnError(
                    "An error occured while migrating dashboard $type for " . $dashboard['owner_id']
                );
            }

            $this->migrateLinesForDashboard($dashboard, $this->db->dbh->lastInsertId());
        }
    }

    private function getInsertStatement($type)
    {
        if ($type === self::OLD_USER_OWNER_TYPE) {
            return "INSERT INTO user_dashboards (user_id, name)
                    VALUES (:owner_id, :name)";
        } elseif ($type === self::OLD_PROJECT_OWNER_TYPE) {
            return "INSERT INTO project_dashboards (project_id, name)
                    VALUES (:owner_id, :name)";
        }

        $this->log->warn("Unkown dashboard type $type, skipping");

        return "";
    }

    private function migrateLinesForDashboard(array $dashboard, $new_dashboard_id)
    {
        $type = $this->getNewOwnerType($dashboard);

        $sql        = "SELECT * FROM layouts_rows WHERE layout_id = :old_layout_id";
        $select_stm = $this->db->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $select_stm->execute(
            [':old_layout_id' => $dashboard['layout_id']]
        );

        foreach ($select_stm->fetchAll() as $line) {
            $sql        = "INSERT INTO dashboards_lines (dashboard_id, dashboard_type, rank)
                           VALUES (:new_dashboard_id, :type, :rank)";
            $insert_stm = $this->db->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $result     = $insert_stm->execute(
                [':new_dashboard_id' => $new_dashboard_id, ':type' => $type, ':rank' => $line['rank']]
            );

            if ($result === false) {
                $this->rollBackOnError(
                    'An error occured while migrating lines for dashboard ' . $new_dashboard_id
                );
            }

            $this->migrateColumnsForLine($dashboard, $line, $new_dashboard_id, $this->db->dbh->lastInsertId());
        }
    }

    private function getNewOwnerType(array $dashboard)
    {
        if ($dashboard['owner_type'] === self::OLD_USER_OWNER_TYPE) {
            return self::NEW_USER_OWNER_TYPE;
        }

        return self::NEW_PROJECT_OWNER_TYPE;
    }

    private function migrateColumnsForLine(array $dashboard, array $line, $new_dashboard_id, $new_line_id)
    {
        $sql        = "SELECT * FROM layouts_rows_columns WHERE layout_row_id = :old_line_id";
        $select_stm = $this->db->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $select_stm->execute(
            [':old_line_id' => $line['id']]
        );

        $columns = $select_stm->fetchAll();
        if (! $columns) {
            return;
        }

        $this->updateLineLayout($columns, $new_line_id);

        $rank = 0;
        foreach ($columns as $column) {
            $sql        = "INSERT INTO dashboards_lines_columns (line_id, rank)
                           VALUES (:new_line_id, :rank)";
            $insert_stm = $this->db->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $result     = $insert_stm->execute(
                [':new_line_id' => $new_line_id, ':rank' => $rank]
            );

            $rank++;

            if ($result === false) {
                $this->rollBackOnError(
                    'An error occured while migrating columns for dashboard ' . $new_dashboard_id
                );
            }

            $this->migrateWidget(
                $dashboard,
                $column,
                $new_dashboard_id,
                $this->db->dbh->lastInsertId()
            );
        }
    }

    private function updateLineLayout(array $columns, $new_line_id)
    {
        $layout = $this->guessTheLayout($columns);

        $sql        = "UPDATE dashboards_lines
                       SET layout = :layout
                       WHERE id = :new_line_id";
        $update_stm = $this->db->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $result     = $update_stm->execute(
            [':layout' => $layout, ':new_line_id' => $new_line_id]
        );

        if ($result === false) {
            $this->rollBackOnError(
                'An error occured while setting the new layout'
            );
        }
    }

    private function migrateWidget(
        array $dashboard,
        array $column,
        $new_dashboard_id,
        $new_column_id
    ) {
        $sql = "SELECT *
                FROM layouts_contents
                WHERE column_id = :old_column_id
                  AND owner_type = :owner_type
                  AND owner_id   = :owner_id
                ORDER BY rank";

        $select_stm = $this->db->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $select_stm->execute(
            [
                ':old_column_id' => $column['id'],
                ':owner_type'    => $dashboard['owner_type'],
                ':owner_id'      => $dashboard['owner_id']
            ]
        );

        foreach ($select_stm->fetchAll() as $widget) {
            $sql = "INSERT INTO dashboards_lines_columns_widgets (column_id, rank, name, content_id, is_minimized)
                    VALUES (:new_column_id, :rank, :name, :content_id, :is_minimized)";

            $select_stm = $this->db->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $result     = $select_stm->execute(
                [
                    ':new_column_id' => $new_column_id,
                    ':rank'          => $widget['rank'],
                    ':name'          => $widget['name'],
                    ':content_id'    => $widget['content_id'],
                    ':is_minimized'  => $widget['is_minimized']
                ]
            );

            if ($result === false) {
                $this->rollBackOnError(
                    'An error occured while migrating widgets for dashboard ' . $new_dashboard_id
                );
            }
        }
    }

    private function guessTheLayout(array $columns)
    {
        $column_number = count($columns);

        if ($column_number === 0 || $column_number === 1) {
            return 'one-column';
        }

        $width_column_one = (int) $columns[0]['width'];
        $width_column_two = (int) $columns[1]['width'];

        if ($column_number === 2) {
            if ($width_column_one === $width_column_two) {
                return 'two-columns';
            }

            if ($width_column_one < $width_column_two) {
                return 'two-columns-small-big';
            }

            return 'two-columns-big-small';
        }

        $width_column_three = (int) $columns[2]['width'];
        if ($column_number === 3) {
            if (($width_column_one === $width_column_two) && ($width_column_two === $width_column_three)) {
                return 'three-columns';
            }

            if ($width_column_one >= $width_column_two && $width_column_one >= $width_column_three) {
                return 'three-columns-big-small-small';
            }

            if ($width_column_two >= $width_column_one && $width_column_two >= $width_column_three) {
                return 'three-columns-small-big-small';
            }

            return 'three-columns-small-small-big';
        }

        return 'too-many-columns';
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
