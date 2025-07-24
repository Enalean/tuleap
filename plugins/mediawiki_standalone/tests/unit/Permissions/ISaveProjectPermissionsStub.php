<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions;

final class ISaveProjectPermissionsStub implements ISaveProjectPermissions
{
    /**
     * @var int[]
     */
    private $captured_readers_ugroup_ids = [];
    /**
     * @var int[]
     */
    private $captured_writers_ugroup_ids = [];
    /**
     * @var int[]
     */
    private $captured_admins_ugroup_ids = [];

    public static function buildSelf(): self
    {
        return new self();
    }

    /**
     * @param \ProjectUGroup[] $readers
     * @param \ProjectUGroup[] $writers
     * @param \ProjectUGroup[] $admins
     */
    #[\Override]
    public function saveProjectPermissions(\Project $project, array $readers, array $writers, array $admins): void
    {
        $this->captured_readers_ugroup_ids = array_map(
            static fn(\ProjectUGroup $user_group) => $user_group->getId(),
            $readers
        );
        $this->captured_writers_ugroup_ids = array_map(
            static fn(\ProjectUGroup $user_group) => $user_group->getId(),
            $writers
        );
        $this->captured_admins_ugroup_ids  = array_map(
            static fn(\ProjectUGroup $user_group) => $user_group->getId(),
            $admins
        );
    }

    /**
     * @return int[]
     */
    public function getCapturedReadersUgroupIds(): array
    {
        return $this->captured_readers_ugroup_ids;
    }

    /**
     * @return int[]
     */
    public function getCapturedWritersUgroupIds(): array
    {
        return $this->captured_writers_ugroup_ids;
    }

    /**
     * @return int[]
     */
    public function getCapturedAdminsUgroupIds(): array
    {
        return $this->captured_admins_ugroup_ids;
    }
}
