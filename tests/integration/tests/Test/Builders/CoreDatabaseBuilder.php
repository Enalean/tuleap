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

namespace Tuleap\Test\Builders;

use ParagonIE\EasyDB\EasyDB;
use Project;
use Tuleap\Project\UserPermissionsDao;
use UserManager;

final readonly class CoreDatabaseBuilder
{
    private UserPermissionsDao $user_permissions_dao;
    private \ProjectDao $project_dao;

    public function __construct(private EasyDB $db)
    {
        $this->user_permissions_dao = new UserPermissionsDao();
        $this->project_dao          = new \ProjectDao();
    }

    public function buildProject(string $name, string $icon = ''): Project
    {
        return $this->insertProject($name, $icon, PROJECT::STATUS_ACTIVE);
    }

    /**
     * @psalm-param Project::STATUS_ACTIVE|Project::STATUS_PENDING|Project::STATUS_SUSPENDED|Project::STATUS_DELETED $status
     */
    public function buildProjectWithStatus(string $name, string $status): Project
    {
        return $this->insertProject($name, '', $status);
    }

    /**
     * @psalm-param Project::STATUS_ACTIVE|Project::STATUS_PENDING|Project::STATUS_SUSPENDED|Project::STATUS_DELETED $status
     */
    private function insertProject(string $name, string $icon, string $status): Project
    {
        $row         = [
            'group_name'      => $name,
            'access'          => 'public',
            'status'          => $status,
            'unix_group_name' => $name,
            'icon_codepoint'  => $icon,
        ];
        $project_id  = (int) $this->db->insertReturnId(
            'groups',
            $row
        );
        $project_row = $this->project_dao->searchById($project_id);
        return new Project($project_row->getRow());
    }

    /**
     * @psalm-param Project::STATUS_ACTIVE|Project::STATUS_PENDING|Project::STATUS_SUSPENDED|Project::STATUS_DELETED $new_status
     */
    public function changeProjectStatus(Project $project, string $new_status): void
    {
        $this->project_dao->updateStatus($project->getID(), $new_status);
    }

    public function buildUser(string $user_name, string $real_name, string $email): \PFUser
    {
        $user_id = $this->db->insertReturnId(
            'user',
            [
                'user_name' => $user_name,
                'email'     => $email,
                'realname'  => $real_name,
            ]
        );

        $user_manager = UserManager::instance();
        $user         = $user_manager->getUserById($user_id);
        if (! $user) {
            throw new \Exception("USer $user_id not found");
        }

        return $user;
    }

    public function buildStaticUserGroup(int $project_id, string $name): int
    {
        return (int) $this->db->insertReturnId(
            'ugroup',
            [
                'name'     => $name,
                'group_id' => $project_id,
            ]
        );
    }

    public function addUserToProjectMembers(int $user_id, int $project_id): void
    {
        $this->user_permissions_dao->addUserAsProjectMember($project_id, $user_id);
    }

    public function addUserToStaticUGroup(int $user_id, int $ugroup_id): void
    {
        $this->db->insert(
            'ugroup_user',
            [
                'ugroup_id'   => $ugroup_id,
                'user_id'     => $user_id,
            ]
        );
    }

    public function addUserToProjectAdmins(int $user_id, int $project_id): void
    {
        $this->user_permissions_dao->addUserAsProjectAdmin($project_id, $user_id);
    }

    public function buildTroveCat(string $name, string $fullpath): int
    {
        return (int) $this->db->insertReturnId(
            'trove_cat',
            [
                'shortname'   => $name,
                'fullname'    => $name,
                'description' => 'Description',
                'fullpath'    => $fullpath,
            ],
        );
    }

    public function addTroveCatToProject(int $trove_cat_id, int $project_id): void
    {
        $this->db->insert(
            'trove_group_link',
            [
                'trove_cat_id' => $trove_cat_id,
                'group_id'     => $project_id,
            ],
        );
    }
}
