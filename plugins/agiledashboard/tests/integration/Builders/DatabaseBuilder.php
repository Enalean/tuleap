<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Builders;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBFactory;

final class DatabaseBuilder
{
    private EasyDB $db;

    public function __construct()
    {
        $this->db = DBFactory::getMainTuleapDBConnection()->getDB();
    }

    private static int $counter = 0;

    public function buildProject(): int
    {
        $count = self::$counter++;
        return (int) $this->db->insertReturnId(
            'groups',
            [
                'group_name'      => "sidebar milestone",
                'access'          => "public",
                'status'          => 'A',
                "unix_group_name" => "sidebar" . $count,
            ]
        );
    }

    public function buildTracker(int $project_id, string $name): int
    {
        return (int) $this->db->insertReturnId(
            'tracker',
            [
                'group_id' => $project_id,
                'name'     => $name,
                'status'   => 'A',
            ]
        );
    }

    public function buildListField(int $tracker_id): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id'       => $tracker_id,
                'formElement_type' => "sb",
                'name'             => "status",
                'label'            => "status",
                'use_it'           => true,
                'scope'            => "P",
            ]
        );

        $this->db->insert(
            'tracker_field_list',
            [
                'field_id'  => $tracker_field_id,
                "bind_type" => "static",
            ]
        );

        $this->db->insert(
            'tracker_field_list_bind_static',
            [
                'field_id'      => $tracker_field_id,
                'is_rank_alpha' => 1,
            ]
        );

        return $tracker_field_id;
    }

    /**
     * @return array{
     *     open: array<int>,
     *     closed: array<int>,
     * }
     */
    public function buildOpenAndClosedValuesForField(int $tracker_field_id, int $tracker_id, array $open_values, array $closed_values): array
    {
        $open_value_id_list = [];
        foreach ($open_values as $value) {
            $open_value_id_list[] = (int) $this->db->insertReturnId(
                'tracker_field_list_bind_static_value',
                [
                    'field_id' => $tracker_field_id,
                    'label'    => $value,
                ]
            );
        }

        $closed_value_id_list = [];
        foreach ($closed_values as $value) {
            $closed_value_id_list[] = (int) $this->db->insertReturnId(
                'tracker_field_list_bind_static_value',
                [
                    'field_id' => $tracker_field_id,
                    'label'    => $value,
                ]
            );
        }

        foreach ($open_value_id_list as $open_value_id) {
            $this->db->insert(
                'tracker_semantic_status',
                [
                    'tracker_id'    => $tracker_id,
                    'field_id'      => $tracker_field_id,
                    'open_value_id' => $open_value_id,
                ]
            );
        }

        return ["open" => $open_value_id_list, "closed" => $closed_value_id_list];
    }

    public function buildPlanning(int $project_id, int $tracker_id): void
    {
        $this->db->insert(
            'plugin_agiledashboard_planning',
            [
                'name'                => "release plan",
                'group_id'            => $project_id,
                'planning_tracker_id' => $tracker_id,
                'backlog_title'       => "backlog",
            ]
        );
    }

    public function buildArtifact(int $tracker_id): int
    {
        return (int) $this->db->insertReturnId(
            'tracker_artifact',
            [
                'tracker_id'               => $tracker_id,
                'last_changeset_id'        => -1,
                'submitted_by'             => 143,
                'submitted_on'             => 1234567890,
                'use_artifact_permissions' => 0,
                'per_tracker_artifact_id'  => 1,
            ]
        );
    }

    public function buildLastChangeset(int $artifact_id): int
    {
        $artifact_changeset_id = (int) $this->db->insertReturnId(
            'tracker_changeset',
            [
                'artifact_id'  => $artifact_id,
                'submitted_by' => 143,
                'submitted_on' => 1234567890,
            ]
        );

        $this->db->update(
            'tracker_artifact',
            [
                'last_changeset_id' => $artifact_changeset_id,
            ],
            [
                'id' => $artifact_id,
            ]
        );

        return $artifact_changeset_id;
    }

    public function buildArtifactLinkField(int $tracker_id): int
    {
        return (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id'       => $tracker_id,
                'formElement_type' => 'art_link',
                'name'             => 'artlink',
                'label'            => 'artlink',
                'use_it'           => true,
                'scope'            => "P",
            ]
        );
    }

    public function buildArtifactLinkValue(int $project_id, int $artifact_id, int $parent_changeset_id, int $artifact_link_field_id, string $type): void
    {
        $changeset_value_id = (int) $this->db->insertReturnId(
            'tracker_changeset_value',
            [
                'changeset_id' => $parent_changeset_id,
                'field_id'     => $artifact_link_field_id,
                'has_changed'  => true,
            ]
        );

        $this->db->insert(
            'tracker_changeset_value_artifactlink',
            [
                'changeset_value_id' => $changeset_value_id,
                'nature'             => $type,
                'artifact_id'        => $artifact_id,
                'keyword'            => 'release',
                'group_id'           => $project_id,
            ]
        );
    }

    public function addStatusValueForArtifact(int $field_id, int $changeset_id, int $bind_open_value_id): void
    {
        $changeset_value_id = (int) $this->db->insertReturnId(
            'tracker_changeset_value',
            [
                'changeset_id' => $changeset_id,
                'field_id'     => $field_id,
                'has_changed'  => true,
            ]
        );

        $this->db->insert(
            'tracker_changeset_value_list',
            [
                'changeset_value_id' => $changeset_value_id,
                'bindvalue_id'       => $bind_open_value_id,
            ]
        );
    }

    public function buildHierarchy(int $parent_id, int $child_id): void
    {
        $this->db->insert(
            'tracker_hierarchy',
            [
                'parent_id' => $parent_id,
                'child_id'  => $child_id,
            ],
        );
    }
}
