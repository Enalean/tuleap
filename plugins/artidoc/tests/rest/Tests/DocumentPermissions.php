<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Tests;

use ProjectUGroup;

final class DocumentPermissions implements \JsonSerializable
{
    /**
     * @param list<string> $readers
     * @param list<string> $writers
     * @param list<string> $managers
     */
    private function __construct(
        private array $readers,
        private array $writers,
        private array $managers,
    ) {
    }

    public static function buildProjectMembersCanManage(int $project_id): self
    {
        $project_members_user_group_id = $project_id . '_' . ProjectUGroup::PROJECT_MEMBERS;

        return new self(
            [(string) ProjectUGroup::REGISTERED],
            [$project_members_user_group_id],
            [$project_members_user_group_id]
        );
    }

    public static function buildProjectAdminsCanManageAndNobodyCanDoAnythingElse(int $project_id): self
    {
        $project_admins_user_group_id = $project_id . '_' . ProjectUGroup::PROJECT_ADMIN;

        return new self(
            [],
            [],
            [$project_admins_user_group_id]
        );
    }

    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [
            'can_read'   => array_map(self::buildJsonUserGroup(...), $this->readers),
            'can_write'  => array_map(self::buildJsonUserGroup(...), $this->writers),
            'can_manage' => array_map(self::buildJsonUserGroup(...), $this->managers),
        ];
    }

    private static function buildJsonUserGroup(string $user_group_id): array
    {
        return ['id' => $user_group_id];
    }
}
