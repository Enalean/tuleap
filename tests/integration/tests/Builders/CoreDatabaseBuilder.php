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

final class CoreDatabaseBuilder
{
    private UserPermissionsDao $user_permissions_dao;

    public function __construct(private readonly EasyDB $db)
    {
        $this->user_permissions_dao = new UserPermissionsDao();
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
}
