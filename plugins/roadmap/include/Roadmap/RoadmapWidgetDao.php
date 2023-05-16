<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class RoadmapWidgetDao extends DataAccessObject
{
    public function __construct(private readonly FilterReportDao $filter_report_dao)
    {
        parent::__construct();
    }

    /**
     * @param int[] $tracker_ids
     */
    public function insertContent(
        int $owner_id,
        string $owner_type,
        string $title,
        array $tracker_ids,
        int $report_id,
        string $default_timescale,
        ?int $lvl1_iteration_tracker_id,
        ?int $lvl2_iteration_tracker_id,
    ): int {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($owner_id, $owner_type, $title, $tracker_ids, $report_id, $default_timescale, $lvl1_iteration_tracker_id, $lvl2_iteration_tracker_id): int {
                $new_id = (int) $db->insertReturnId(
                    'plugin_roadmap_widget',
                    [
                        'owner_id'                  => $owner_id,
                        'owner_type'                => $owner_type,
                        'title'                     => $title,
                        'default_timescale'         => $default_timescale,
                        'lvl1_iteration_tracker_id' => $lvl1_iteration_tracker_id,
                        'lvl2_iteration_tracker_id' => $lvl2_iteration_tracker_id,
                    ]
                );

                $db->insertMany(
                    'plugin_roadmap_widget_trackers',
                    array_map(
                        fn ($tracker_id) => ['plugin_roadmap_widget_id' => $new_id, 'tracker_id' => $tracker_id],
                        $tracker_ids,
                    )
                );

                if (count($tracker_ids) === 1 && $report_id) {
                    $this->filter_report_dao->saveReportId($new_id, $report_id);
                }

                return $new_id;
            }
        );
    }

    public function cloneContent(
        int $id,
        int $destination_owner_id,
        string $destination_owner_type,
    ): int {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($id, $destination_owner_id, $destination_owner_type): int {
                $sql = 'INSERT INTO plugin_roadmap_widget (owner_id, owner_type, title, default_timescale, lvl1_iteration_tracker_id, lvl2_iteration_tracker_id)
                        SELECT  ?, ?, title, default_timescale, lvl1_iteration_tracker_id, lvl2_iteration_tracker_id
                        FROM plugin_roadmap_widget
                        WHERE id = ?';

                $db->run(
                    $sql,
                    $destination_owner_id,
                    $destination_owner_type,
                    $id
                );

                $new_id = (int) $db->lastInsertId();

                if ($new_id) {
                    $db->run(
                        'INSERT INTO plugin_roadmap_widget_trackers (plugin_roadmap_widget_id, tracker_id)
                        SELECT ?, plugin_roadmap_widget_trackers.tracker_id
                        FROM plugin_roadmap_widget_trackers
                            INNER JOIN plugin_roadmap_widget
                                ON (plugin_roadmap_widget_trackers.plugin_roadmap_widget_id = plugin_roadmap_widget.id)
                        WHERE id = ?',
                        $new_id,
                        $id,
                    );
                }

                return $new_id;
            }
        );
    }

    public function searchById(int $id): ?array
    {
        $sql = "SELECT *
                FROM plugin_roadmap_widget
                WHERE id = ?";

        return $this->getDB()->row($sql, $id);
    }

    public function searchSelectedTrackers(int $id): ?array
    {
        $sql = "SELECT tracker_id
                FROM plugin_roadmap_widget_trackers
                WHERE plugin_roadmap_widget_id = ?";

        return $this->getDB()->col($sql, 0, $id);
    }

    public function searchContent(int $id, int $owner_id, string $owner_type): ?array
    {
        $sql = "SELECT *
                FROM plugin_roadmap_widget
                WHERE id = ?
                  AND owner_id = ?
                  AND owner_type = ?";

        return $this->getDB()->row($sql, $id, $owner_id, $owner_type);
    }

    /**
     * @param int[] $tracker_ids
     */
    public function update(
        int $id,
        int $owner_id,
        string $owner_type,
        string $title,
        array $tracker_ids,
        int $report_id,
        string $default_timescale,
        ?int $lvl1_iteration_tracker_id,
        ?int $lvl2_iteration_tracker_id,
    ): void {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($id, $owner_id, $owner_type, $title, $tracker_ids, $report_id, $default_timescale, $lvl1_iteration_tracker_id, $lvl2_iteration_tracker_id) {
                $db->update(
                    'plugin_roadmap_widget',
                    [
                        'title'                     => $title,
                        'default_timescale'         => $default_timescale,
                        'lvl1_iteration_tracker_id' => $lvl1_iteration_tracker_id,
                        'lvl2_iteration_tracker_id' => $lvl2_iteration_tracker_id,
                    ],
                    [
                        'owner_id'   => $owner_id,
                        'owner_type' => $owner_type,
                        'id'         => $id,
                    ]
                );

                $db->delete('plugin_roadmap_widget_trackers', ['plugin_roadmap_widget_id' => $id]);

                $db->insertMany(
                    'plugin_roadmap_widget_trackers',
                    array_map(
                        fn ($tracker_id) => ['plugin_roadmap_widget_id' => $id, 'tracker_id' => $tracker_id],
                        $tracker_ids,
                    )
                );

                if (count($tracker_ids) === 1 && $report_id) {
                    $this->filter_report_dao->saveReportId($id, $report_id);
                } else {
                    $this->filter_report_dao->deleteByWidget($id);
                }
            }
        );
    }

    public function delete(int $id, int $owner_id, string $owner_type): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($id, $owner_id, $owner_type) {
                $db->delete(
                    'plugin_roadmap_widget',
                    [
                        'owner_id'   => $owner_id,
                        'owner_type' => $owner_type,
                        'id'         => $id,
                    ]
                );
                $db->delete('plugin_roadmap_widget_trackers', ['plugin_roadmap_widget_id' => $id]);

                $this->filter_report_dao->deleteByWidget($id);
            }
        );
    }
}
