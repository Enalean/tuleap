<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Tests\Builders;

use ParagonIE\EasyDB\EasyDB;

final class DatabaseBuilder
{
    public function __construct(private readonly EasyDB $db)
    {
    }

    private static int $counter = 0;

    public function buildProject(): int
    {
        $count = self::$counter++;
        return (int) $this->db->insertReturnId(
            'groups',
            [
                'group_name' => "cross tracker",
                'access' => "public",
                'status' => 'A',
                "unix_group_name" => "xtracker-" . $count,
            ]
        );
    }

    public function buildTracker(int $project_id, string $name): int
    {
        return (int) $this->db->insertReturnId(
            'tracker',
            [
                'group_id' => $project_id,
                'name' => $name,
                'status' => 'A',
            ]
        );
    }

    public function buildIntField(int $tracker_id): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id' => $tracker_id,
                'formElement_type' => "int",
                'name' => "initial_effort",
                'label' => "initial_effort",
                'use_it' => true,
                'scope' => "P",
            ]
        );

        $this->db->insert(
            'tracker_field_int',
            [
                'field_id' => $tracker_field_id,
            ]
        );

        return $tracker_field_id;
    }

    public function buildFloatField(int $tracker_id): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id' => $tracker_id,
                'formElement_type' => "float",
                'name' => "initial_effort",
                'label' => "initial_effort",
                'use_it' => true,
                'scope' => "P",
            ]
        );

        $this->db->insert(
            'tracker_field_float',
            [
                'field_id' => $tracker_field_id,
            ]
        );

        return $tracker_field_id;
    }

    public function buildComputedField(int $tracker_id): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id' => $tracker_id,
                'formElement_type' => "computed",
                'name' => "initial_effort",
                'label' => "initial_effort",
                'use_it' => true,
                'scope' => "P",
            ]
        );

        $this->db->insert(
            'tracker_field_computed',
            [
                'field_id' => $tracker_field_id,
            ]
        );

        return $tracker_field_id;
    }

    public function buildArtifact(int $tracker_id): int
    {
        return (int) $this->db->insertReturnId(
            'tracker_artifact',
            [
                'tracker_id' => $tracker_id,
                'last_changeset_id' => -1,
                'submitted_by' => 143,
                'submitted_on' => 1234567890,
                'use_artifact_permissions' => 0,
                'per_tracker_artifact_id' => 1,
            ]
        );
    }

    public function buildLastChangeset(int $artifact_id): int
    {
        $artifact_changeset_id = (int) $this->db->insertReturnId(
            'tracker_changeset',
            [
                'artifact_id' => $artifact_id,
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

    public function buildIntValue(int $parent_changeset_id, int $int_field_id, int $value): void
    {
        $changeset_value_id = (int) $this->db->insertReturnId(
            'tracker_changeset_value',
            [
                'changeset_id' => $parent_changeset_id,
                'field_id' => $int_field_id,
                'has_changed' => true,
            ]
        );

        $this->db->insert(
            'tracker_changeset_value_int',
            [
                'changeset_value_id' => $changeset_value_id,
                'value' => $value,
            ]
        );
    }

    public function buildFloatValue(int $parent_changeset_id, int $int_field_id, float $value): void
    {
        $changeset_value_id = (int) $this->db->insertReturnId(
            'tracker_changeset_value',
            [
                'changeset_id' => $parent_changeset_id,
                'field_id' => $int_field_id,
                'has_changed' => true,
            ]
        );

        $this->db->insert(
            'tracker_changeset_value_float',
            [
                'changeset_value_id' => $changeset_value_id,
                'value' => $value,
            ]
        );
    }
}
