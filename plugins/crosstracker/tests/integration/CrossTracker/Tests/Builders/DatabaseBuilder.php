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
use Project;
use Tracker;
use Tuleap\Project\UserPermissionsDao;
use UserManager;

final class DatabaseBuilder
{
    private UserPermissionsDao $user_permissions_dao;

    public function __construct(private readonly EasyDB $db)
    {
        $this->user_permissions_dao = new UserPermissionsDao();
    }

    public function cleanUp(): void
    {
        $this->db->run('DELETE FROM tracker_artifact');
        $this->db->run('DELETE FROM tracker_field');
        $this->db->run('DELETE FROM tracker_field_float');
        $this->db->run('DELETE FROM tracker_field_int');
        $this->db->run('DELETE FROM tracker_field_computed');
        $this->db->run('DELETE FROM tracker_changeset');
        $this->db->run('DELETE FROM tracker_changeset_value');
        $this->db->run('DELETE FROM tracker_changeset_value_int');
        $this->db->run('DELETE FROM tracker_changeset_value_float');
        $this->db->run('DELETE FROM user WHERE user_id > 101');
        $this->db->run('DELETE FROM user_group WHERE user_id > 101');
        $this->db->run('DELETE FROM `groups` WHERE group_id > 100');
        $this->db->run('DELETE FROM tracker');
    }

    public function buildProject(): Project
    {
        $row         = [
            'group_name' => "cross tracker",
            'access' => "public",
            'status' => 'A',
            "unix_group_name" => "cross-tracker-comparison",
        ];
        $project_id  = (int) $this->db->insertReturnId(
            'groups',
            $row
        );
        $dao         = new \ProjectDao();
        $project_row = $dao->searchById($project_id);
        return new Project($project_row->getRow());
    }

    public function buildUser(string $user_name, string $real_name, string $email): \PFUser
    {
        $user_id = $this->db->insertReturnId(
            'user',
            [
                'user_name' => $user_name,
                'email' => $email,
                'realname' => $real_name,
            ]
        );

        $user_manager = UserManager::instance();
        $user         = $user_manager->getUserById($user_id);
        if (! $user) {
            throw new \Exception("USer $user_id not found");
        }

        return $user;
    }

    public function addUserToProjectMembers(int $user_id, int $project_id): void
    {
        $this->user_permissions_dao->addUserAsProjectMember($project_id, $user_id);
    }

    public function buildTracker(int $project_id, string $name): Tracker
    {
        $factory    = \TrackerFactory::instance();
        $tracker_id = (int) $this->db->insertReturnId(
            'tracker',
            [
                'group_id' => $project_id,
                'name' => $name,
                'status' => 'A',
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
                'tracker_id' => $tracker_id,
                'formElement_type' => "int",
                'name' => $name,
                'label' => $name,
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

    public function buildFloatField(int $tracker_id, string $name): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id' => $tracker_id,
                'formElement_type' => "float",
                'name' => $name,
                'label' => $name,
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

    public function buildComputedField(int $tracker_id, string $name): int
    {
        $tracker_field_id = (int) $this->db->insertReturnId(
            'tracker_field',
            [
                'tracker_id' => $tracker_id,
                'formElement_type' => "computed",
                'name' => $name,
                'label' => $name,
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
