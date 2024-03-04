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

namespace Tuleap\Tracker\Test\Builders;

use ParagonIE\EasyDB\EasyDB;
use Tracker;

final class TrackerDatabaseBuilder
{
    public function __construct(private readonly EasyDB $db)
    {
    }

    public function buildTracker(int $project_id, string $name): Tracker
    {
        $factory    = \TrackerFactory::instance();
        $tracker_id = (int) $this->db->insertReturnId(
            'tracker',
            [
                'group_id' => $project_id,
                'name'     => $name,
                'status'   => 'A',
            ]
        );
        $tracker    = $factory->getTrackerById($tracker_id);
        if (! $tracker) {
            throw new \Exception("tracker not found");
        }

        return $tracker;
    }

    public function buildIntField(int $tracker_id, string $name): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id'       => $tracker_id,
                'formElement_type' => "int",
                'name'             => $name,
                'label'            => $name,
                'use_it'           => true,
                'scope'            => "P",
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

    public function buildFloatField(int $tracker_id, string $name): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id'       => $tracker_id,
                'formElement_type' => "float",
                'name'             => $name,
                'label'            => $name,
                'use_it'           => true,
                'scope'            => "P",
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

    public function buildComputedField(int $tracker_id, string $name): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id'       => $tracker_id,
                'formElement_type' => "computed",
                'name'             => $name,
                'label'            => $name,
                'use_it'           => true,
                'scope'            => "P",
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

    public function buildTextField(int $tracker_id, string $name): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id'       => $tracker_id,
                'formElement_type' => "text",
                'name'             => $name,
                'label'            => $name,
                'use_it'           => true,
                'scope'            => "P",
            ]
        );

        $this->db->insert(
            'tracker_field_text',
            [
                'field_id' => $tracker_field_id,
            ]
        );

        return $tracker_field_id;
    }

    public function buildStringField(int $tracker_id, string $name): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id'       => $tracker_id,
                'formElement_type' => "string",
                'name'             => $name,
                'label'            => $name,
                'use_it'           => true,
                'scope'            => "P",
            ]
        );

        $this->db->insert(
            'tracker_field_string',
            [
                'field_id' => $tracker_field_id,
            ]
        );

        return $tracker_field_id;
    }

    public function buildDateField(int $tracker_id, string $name, bool $display_time): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id'       => $tracker_id,
                'formElement_type' => "date",
                'name'             => $name,
                'label'            => $name,
                'use_it'           => true,
                'scope'            => "P",
            ]
        );

        $this->db->insert(
            'tracker_field_date',
            [
                'field_id'     => $tracker_field_id,
                'display_time' => $display_time,
            ]
        );

        return $tracker_field_id;
    }

    public function setReadPermission(int $field_id, int $user_group_id): void
    {
        $this->db->insert(
            'permissions',
            [
                'permission_type' => \Tracker_FormElement::PERMISSION_READ,
                'object_id'       => (string) $field_id,
                'ugroup_id'       => $user_group_id,
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

    private function buildChangesetValue(int $parent_changeset_id, int $field_id): int
    {
        return (int) $this->db->insertReturnId(
            'tracker_changeset_value',
            [
                'changeset_id' => $parent_changeset_id,
                'field_id'     => $field_id,
                'has_changed'  => true,
            ]
        );
    }

    public function buildIntValue(int $parent_changeset_id, int $int_field_id, int $value): void
    {
        $changeset_value_id = $this->buildChangesetValue($parent_changeset_id, $int_field_id);

        $this->db->insert(
            'tracker_changeset_value_int',
            [
                'changeset_value_id' => $changeset_value_id,
                'value'              => $value,
            ]
        );
    }

    public function buildFloatValue(int $parent_changeset_id, int $float_field_id, float $value): void
    {
        $changeset_value_id = $this->buildChangesetValue($parent_changeset_id, $float_field_id);

        $this->db->insert(
            'tracker_changeset_value_float',
            [
                'changeset_value_id' => $changeset_value_id,
                'value'              => $value,
            ]
        );
    }

    public function buildTextValue(int $parent_changeset_id, int $text_field_id, string $value, string $format): void
    {
        $changeset_value_id = $this->buildChangesetValue($parent_changeset_id, $text_field_id);

        $this->db->insert(
            'tracker_changeset_value_text',
            [
                'changeset_value_id' => $changeset_value_id,
                'value'              => $value,
                'body_format'        => $format,
            ]
        );
    }

    public function buildDateValue(int $parent_changeset_id, int $date_field_id, int $value): void
    {
        $changeset_value_id = $this->buildChangesetValue($parent_changeset_id, $date_field_id);

        $this->db->insert(
            'tracker_changeset_value_date',
            [
                'changeset_value_id' => $changeset_value_id,
                'value'              => $value,
            ]
        );
    }

    public function buildListValue(int $parent_changeset_id, int $list_field_id, int $bind_value_id): void
    {
        $changeset_value_id = $this->buildChangesetValue($parent_changeset_id, $list_field_id);

        $this->db->insert(
            'tracker_changeset_value_list',
            [
                'changeset_value_id' => $changeset_value_id,
                'bindvalue_id'       => $bind_value_id,
            ]
        );
    }

    private function buildListField(int $tracker_id, string $name, string $list_type, string $bind_type): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id'       => $tracker_id,
                'formElement_type' => $list_type,
                'name'             => $name,
                'label'            => $name,
                'use_it'           => true,
                'scope'            => 'P',
            ]
        );

        $this->db->insert(
            'tracker_field_list',
            [
                'field_id'  => $tracker_field_id,
                'bind_type' => $bind_type,
            ]
        );

        return $tracker_field_id;
    }

    public function buildStaticListField(int $tracker_id, string $name, string $list_type): int
    {
        $tracker_field_id = $this->buildListField($tracker_id, $name, $list_type, 'static');

        $this->db->insert(
            'tracker_field_list_bind_static',
            [
                'field_id'      => $tracker_field_id,
                'is_rank_alpha' => 1,
            ]
        );

        return $tracker_field_id;
    }

    public function buildUserGroupListField(int $tracker_id, string $name, string $list_type): int
    {
        return $this->buildListField($tracker_id, $name, $list_type, 'ugroups');
    }

    public function buildUserListField(int $tracker_id, string $name, string $list_type): int
    {
        $tracker_field_id = $this->buildListField($tracker_id, $name, $list_type, 'users');

        $this->db->insert(
            'tracker_field_list_bind_users',
            [
                'field_id' => $tracker_field_id,
                'value_function' => 'group_members',
            ]
        );

        return $tracker_field_id;
    }

    /**
     * @param string[] $values
     * @return array<string, int>
     */
    public function buildValuesForStaticListField(int $tracker_field_id, array $values): array
    {
        $ids_list = [];
        foreach ($values as $value) {
            $ids_list[$value] = (int) $this->db->insertReturnId(
                'tracker_field_list_bind_static_value',
                [
                    'field_id' => $tracker_field_id,
                    'label'    => $value,
                ]
            );
        }

        return $ids_list;
    }

    /**
     * @param int[] $ugroup_ids
     * @return array<int, int>
     */
    public function buildValuesForUserGroupListField(int $tracker_field_id, array $ugroup_ids): array
    {
        $ids_list = [];
        foreach ($ugroup_ids as $ugroup_id) {
            $ids_list[$ugroup_id] = (int) $this->db->insertReturnId(
                'tracker_field_list_bind_ugroups_value',
                [
                    'field_id'  => $tracker_field_id,
                    'ugroup_id' => $ugroup_id,
                ]
            );
        }

        return $ids_list;
    }

    /**
     * @return array{
     *     open: array<int>,
     *     closed: array<int>,
     * }
     */
    public function buildOpenAndClosedValuesForField(int $tracker_field_id, int $tracker_id, array $open_values, array $closed_values): array
    {
        $open_value_id_list   = array_values($this->buildValuesForStaticListField($tracker_field_id, $open_values));
        $closed_value_id_list = array_values($this->buildValuesForStaticListField($tracker_field_id, $closed_values));

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
        $changeset_value_id = $this->buildChangesetValue($parent_changeset_id, $artifact_link_field_id);

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
        $changeset_value_id = $this->buildChangesetValue($changeset_id, $field_id);

        $this->db->insert(
            'tracker_changeset_value_list',
            [
                'changeset_value_id' => $changeset_value_id,
                'bindvalue_id'       => $bind_open_value_id,
            ]
        );
    }
}
