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

namespace Tuleap\Kanban;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

class KanbanDao extends DataAccessObject
{
    public function duplicateKanbans(array $tracker_mapping, array $field_mapping, array $report_mapping): void
    {
        if (empty($tracker_mapping)) {
            return;
        }

        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($tracker_mapping, $field_mapping, $report_mapping) {
            $tracker_ids = EasyStatement::open()->in('?*', array_keys($tracker_mapping));

            $sql = "SELECT *
                FROM plugin_agiledashboard_kanban_configuration
                WHERE tracker_id IN ($tracker_ids)";

            foreach ($this->getDB()->run($sql, ...$tracker_ids->values()) as $row) {
                $old_kanban_id = $row['id'];
                $new_kanban_id = $this->create($row['name'], (bool) $row['is_promoted'], $tracker_mapping[$row['tracker_id']]);
                $this->duplicateColumns($old_kanban_id, $new_kanban_id, $field_mapping);
                $this->duplicateReports($old_kanban_id, $new_kanban_id, $report_mapping);
            }
        });
    }

    private function duplicateReports(int $old_kanban_id, int $new_kanban_id, array $report_mapping): void
    {
        $parameters = [];
        $when       = [];
        foreach ($report_mapping as $old_value_id => $new_value_id) {
            $when_fragment = $this->convertValueIdToWhenThenStatement($new_value_id, $old_value_id);

            $when[]     = $when_fragment->sql;
            $parameters = [...$parameters, ...$when_fragment->parameters];
        }
        $new_report_id = "CASE report_id " . implode(' ', $when) . " END";

        $sql = "INSERT INTO plugin_agiledashboard_kanban_tracker_reports (kanban_id, report_id)
                SELECT ?, $new_report_id
                FROM plugin_agiledashboard_kanban_tracker_reports
                WHERE kanban_id = ?";

        $this->getDB()->run($sql, ...[$new_kanban_id, ...$parameters, $old_kanban_id]);
    }

    private function duplicateColumns(int $old_kanban_id, int $new_kanban_id, array $field_mapping): void
    {
        $value_mapping = [];
        foreach ($field_mapping as $mapping) {
            $value_mapping += $mapping['values'];
        }
        $value_mapping = array_filter($value_mapping);

        if (empty($value_mapping)) {
            return;
        }

        $parameters = [];
        $when       = [];
        foreach ($value_mapping as $old_value_id => $new_value_id) {
            $when_fragment = $this->convertValueIdToWhenThenStatement($new_value_id, $old_value_id);

            $when[]     = $when_fragment->sql;
            $parameters = [...$parameters, ...$when_fragment->parameters];
        }
        $new_value_id = "CASE value_id " . implode(' ', $when) . " END";

        $sql = "INSERT INTO plugin_agiledashboard_kanban_configuration_column (kanban_id, value_id, wip_limit)
                SELECT ?, $new_value_id, wip_limit
                FROM plugin_agiledashboard_kanban_configuration_column
                WHERE kanban_id = ?";

        $this->getDB()->run($sql, ...[$new_kanban_id, ...$parameters, $old_kanban_id]);
    }

    private function convertValueIdToWhenThenStatement(int $new_value_id, int $old_value_id): ParametrizedSQLFragment
    {
        return new ParametrizedSQLFragment(
            "WHEN ? THEN ?",
            [$old_value_id, $new_value_id],
        );
    }

    public function create(string $kanban_name, bool $is_promoted, int $tracker_kanban): int
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_agiledashboard_kanban_configuration',
            [
                'tracker_id'  => $tracker_kanban,
                'name'        => $kanban_name,
                'is_promoted' => $is_promoted,
            ],
        );
    }

    public function updateLabel(int $kanban_id, string $kanban_name): void
    {
        $sql = 'UPDATE plugin_agiledashboard_kanban_configuration
                SET name = ?
                WHERE id = ?';

        $this->getDB()->run($sql, $kanban_name, $kanban_id);
    }

    public function updatePromotion(int $kanban_id, bool $is_promoted): void
    {
        $sql = 'UPDATE plugin_agiledashboard_kanban_configuration
                SET is_promoted = ?
                WHERE id = ?';

        $this->getDB()->run($sql, $is_promoted, $kanban_id);
    }

    public function delete(int $kanban_id): void
    {
        $this->getDB()->tryFlatTransaction(static function (EasyDB $db) use ($kanban_id) {
            $db->run(
                "DELETE FROM plugin_agiledashboard_kanban_configuration_column
                WHERE kanban_id = ?",
                $kanban_id
            );
            $db->run(
                "DELETE FROM plugin_agiledashboard_kanban_configuration
                WHERE id = ?",
                $kanban_id
            );
            $db->run(
                "DELETE config.*
                FROM plugin_agiledashboard_kanban_widget_config AS config
                INNER JOIN plugin_agiledashboard_kanban_widget  AS kanban_widget
                    ON config.widget_id = kanban_widget.id
                WHERE kanban_widget.kanban_id = ?",
                $kanban_id
            );
            $db->run(
                "DELETE FROM plugin_agiledashboard_kanban_widget WHERE kanban_id = ?",
                $kanban_id
            );
        });
    }

    /**
     * @return array{id: int, tracker_id: int, is_promoted: int, name: string, group_id: int}|null
     */
    public function getKanbanByTrackerId(int $tracker_kanban): ?array
    {
        $sql = "SELECT kanban_config.*, tracker.group_id
                FROM plugin_agiledashboard_kanban_configuration AS kanban_config
                    INNER JOIN tracker
                    ON (tracker.id = kanban_config.tracker_id)
                WHERE kanban_config.tracker_id = ?";

        return $this->getDB()->row($sql, $tracker_kanban);
    }

    /**
     * @return array{id: int, tracker_id: int, is_promoted: int, name: string, group_id: int}|null
     */
    public function getKanbanById(int $kanban_id): ?array
    {
        $sql = "SELECT kanban_config.*, tracker.group_id
                FROM plugin_agiledashboard_kanban_configuration AS kanban_config
                    INNER JOIN tracker
                    ON (tracker.id = kanban_config.tracker_id)
                WHERE kanban_config.id = ?";

        return $this->getDB()->row($sql, $kanban_id);
    }

    /**
     * @return list<array{id: int, tracker_id: int, is_promoted: int, name: string, group_id: int}>
     */
    public function getKanbansForProject(int $project_id): array
    {
        $sql = "SELECT kanban_config.*, tracker.group_id
                FROM plugin_agiledashboard_kanban_configuration AS kanban_config
                    INNER JOIN tracker
                    ON (tracker.id = kanban_config.tracker_id)
                WHERE tracker.group_id = ?
                ORDER BY kanban_config.name ASC";

        return $this->getDB()->run($sql, $project_id);
    }

    public function countKanbanCards(): int
    {
        $sql = 'SELECT count(*) as nb
                FROM plugin_agiledashboard_kanban_configuration AS kanban
                INNER JOIN tracker_artifact
                  ON kanban.tracker_id = tracker_artifact.tracker_id';

        return (int) $this->getDB()->cell($sql);
    }

    public function countKanbanCardsAfter(int $timestamp): int
    {
        $sql = 'SELECT count(*) as nb
                FROM plugin_agiledashboard_kanban_configuration AS kanban
                INNER JOIN tracker_artifact
                  ON kanban.tracker_id = tracker_artifact.tracker_id
                AND tracker_artifact.submitted_on > ?';

        return (int) $this->getDB()->cell($sql, $timestamp);
    }
}
