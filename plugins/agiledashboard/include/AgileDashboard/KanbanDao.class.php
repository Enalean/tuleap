<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

class AgileDashboard_KanbanDao extends DataAccessObject
{

    public function duplicateKanbans(array $tracker_mapping, array $field_mapping, array $report_mapping)
    {
        if (empty($tracker_mapping)) {
            return;
        }

        $tracker_ids = $this->da->escapeIntImplode(array_keys($tracker_mapping));

        $sql = "SELECT *
                FROM plugin_agiledashboard_kanban_configuration
                WHERE tracker_id IN ($tracker_ids)";

        foreach ($this->retrieve($sql) as $row) {
            $old_kanban_id = $row['id'];
            $new_kanban_id = $this->create($row['name'], $tracker_mapping[$row['tracker_id']]);
            $this->duplicateColumns($old_kanban_id, $new_kanban_id, $field_mapping);
            $this->duplicateReports($old_kanban_id, $new_kanban_id, $report_mapping);
        }
    }

    private function duplicateReports($old_kanban_id, $new_kanban_id, array $report_mapping)
    {
        array_walk($report_mapping, array($this, 'convertValueIdToWhenThenStatement'));
        $new_report_id = "CASE report_id " . implode(' ', $report_mapping) . " END";

        $sql = "INSERT INTO plugin_agiledashboard_kanban_tracker_reports (kanban_id, report_id)
                SELECT $new_kanban_id, $new_report_id
                FROM plugin_agiledashboard_kanban_tracker_reports
                WHERE kanban_id = $old_kanban_id";
        $this->update($sql);
    }

    private function duplicateColumns($old_kanban_id, $new_kanban_id, array $field_mapping)
    {
        $value_mapping = array();
        foreach ($field_mapping as $mapping) {
            $value_mapping += $mapping['values'];
        }
        $value_mapping = array_filter($value_mapping);

        if (empty($value_mapping)) {
            return;
        }

        array_walk(
            $value_mapping,
            function (&$new_value_id, $old_value_id): void {
                $this->convertValueIdToWhenThenStatement($new_value_id, $old_value_id);
            }
        );
        $new_value_id = "CASE value_id " . implode(' ', $value_mapping) . " END";

        $sql = "INSERT INTO plugin_agiledashboard_kanban_configuration_column (kanban_id, value_id, wip_limit)
                SELECT $new_kanban_id, $new_value_id, wip_limit
                FROM plugin_agiledashboard_kanban_configuration_column
                WHERE kanban_id = $old_kanban_id";

        $this->update($sql);
    }

    private function convertValueIdToWhenThenStatement(&$new_value_id, $old_value_id)
    {
        $new_value_id = $this->da->escapeInt($new_value_id);
        $old_value_id = $this->da->escapeInt($old_value_id);

        $new_value_id = "WHEN $old_value_id THEN $new_value_id";
    }

    public function create($kanban_name, $tracker_kanban)
    {
        $tracker_kanban = $this->da->escapeInt($tracker_kanban);
        $kanban_name    = $this->da->quoteSmart($kanban_name);

        $sql = "INSERT INTO plugin_agiledashboard_kanban_configuration (tracker_id, name)
                VALUES ($tracker_kanban, $kanban_name)";

        return $this->updateAndGetLastId($sql);
    }

    public function save($kanban_id, $kanban_name)
    {
        $kanban_id   = $this->da->escapeInt($kanban_id);
        $kanban_name = $this->da->quoteSmart($kanban_name);

        $sql = "UPDATE plugin_agiledashboard_kanban_configuration
                SET name = $kanban_name
                WHERE id = $kanban_id";

        return $this->update($sql);
    }

    public function delete($kanban_id)
    {
        $kanban_id   = $this->da->escapeInt($kanban_id);

        $this->startTransaction();

        $sql = "DELETE FROM plugin_agiledashboard_kanban_configuration_column
                WHERE kanban_id = $kanban_id";

        $this->update($sql);

        $sql = "DELETE FROM plugin_agiledashboard_kanban_configuration
                WHERE id = $kanban_id";

        $this->update($sql);

        $sql = "DELETE config.*
                FROM plugin_agiledashboard_kanban_widget_config AS config
                INNER JOIN plugin_agiledashboard_kanban_widget  AS kanban_widget
                    ON config.widget_id = kanban_widget.id
                WHERE kanban_widget.kanban_id = $kanban_id";

        $this->update($sql);

        $sql = "DELETE FROM plugin_agiledashboard_kanban_widget WHERE kanban_id = $kanban_id";
        $this->update($sql);

        $this->commit();
    }

    public function getKanbanByTrackerId($tracker_kanban)
    {
        $tracker_kanban = $this->da->escapeInt($tracker_kanban);

        $sql = "SELECT kanban_config.*, tracker.group_id
                FROM plugin_agiledashboard_kanban_configuration AS kanban_config
                    INNER JOIN tracker
                    ON (tracker.id = kanban_config.tracker_id)
                WHERE kanban_config.tracker_id = $tracker_kanban";

        return $this->retrieve($sql);
    }

    public function getKanbanById($kanban_id)
    {
        $kanban_id = $this->da->escapeInt($kanban_id);

        $sql = "SELECT kanban_config.*, tracker.group_id
                FROM plugin_agiledashboard_kanban_configuration AS kanban_config
                    INNER JOIN tracker
                    ON (tracker.id = kanban_config.tracker_id)
                WHERE kanban_config.id = $kanban_id";

        return $this->retrieve($sql);
    }

    public function getTrackersWithKanbanUsageAndHierarchy($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT tracker.id,
                    tracker.name,
                    COALESCE(
                        kanban_config.name,
                        planning.planning_tracker_id,
                        backlog.tracker_id,
                        TH1.parent_id,
                        TH2.child_id
                    ) AS used
                FROM tracker
                    LEFT JOIN plugin_agiledashboard_kanban_configuration AS kanban_config
                    ON (tracker.id = kanban_config.tracker_id)
                    LEFT JOIN plugin_agiledashboard_planning AS planning
                    ON (tracker.id = planning.planning_tracker_id)
                    LEFT JOIN plugin_agiledashboard_planning_backlog_tracker AS backlog
                    ON (tracker.id = backlog.tracker_id)
                    LEFT JOIN tracker_hierarchy AS TH1
                    ON (tracker.id = TH1.parent_id)
                    LEFT JOIN tracker_hierarchy AS TH2
                    ON (tracker.id = TH2.child_id)
                WHERE tracker.group_id = $project_id
                    AND tracker.deletion_date IS NULL
                GROUP BY tracker.id
                ORDER BY tracker.name ASC";

        return $this->retrieve($sql);
    }

    public function getKanbansForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT kanban_config.*, tracker.group_id
                FROM plugin_agiledashboard_kanban_configuration AS kanban_config
                    INNER JOIN tracker
                    ON (tracker.id = kanban_config.tracker_id)
                WHERE tracker.group_id = $project_id
                ORDER BY kanban_config.name ASC";

        return $this->retrieve($sql);
    }

    public function countKanbanCards(): int
    {
        $sql = 'SELECT count(*) as nb
                FROM plugin_agiledashboard_kanban_configuration AS kanban
                INNER JOIN tracker_artifact
                  ON kanban.tracker_id = tracker_artifact.tracker_id';

        $res = $this->retrieveFirstRow($sql);

        return (!$res) ? 0 : (int) $res['nb'];
    }

    public function countKanbanCardsAfter(int $timestamp): int
    {
        $sql = 'SELECT count(*) as nb
                FROM plugin_agiledashboard_kanban_configuration AS kanban
                INNER JOIN tracker_artifact
                  ON kanban.tracker_id = tracker_artifact.tracker_id
                AND tracker_artifact.submitted_on > ' . $this->da->escapeInt($timestamp);

        $res = $this->retrieveFirstRow($sql);

        return (!$res) ? 0 : (int) $res['nb'];
    }
}
