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

final class ProjectPermissionsRetriever
{
    public function __construct(private ISearchByProject $dao)
    {
    }

    public function getProjectPermissions(\Project $project): ProjectPermissions
    {
        $readers = [];
        $writers = [];
        $admins  = [];
        foreach ($this->dao->searchByProject($project) as $perm) {
            if ($perm['permission'] === PermissionRead::NAME) {
                $readers[] = $perm['ugroup_id'];
                continue;
            }

            if ($perm['permission'] === PermissionWrite::NAME) {
                $writers[] = $perm['ugroup_id'];
                continue;
            }

            if ($perm['permission'] === PermissionAdmin::NAME) {
                $admins[] = $perm['ugroup_id'];
                continue;
            }
        }

        return new ProjectPermissions(
            $this->getReadersUgroupIds($readers),
            $this->getWritersUgroupIds($writers),
            $this->getAdminsUgroupIds($admins),
        );
    }

    /**
     * @param int[] $readers
     *
     * @return int[]
     */
    private function getReadersUgroupIds(array $readers): array
    {
        return empty($readers)
            ? [\ProjectUGroup::PROJECT_MEMBERS]
            : $readers;
    }

    /**
     * @param int[] $writers
     *
     * @return int[]
     */
    private function getWritersUgroupIds(array $writers): array
    {
        return empty($writers)
            ? [\ProjectUGroup::PROJECT_MEMBERS]
            : $writers;
    }

    /**
     * @param int[] $admins
     *
     * @return int[]
     */
    private function getAdminsUgroupIds(array $admins): array
    {
        if (! in_array(\ProjectUGroup::PROJECT_ADMIN, $admins, true)) {
            array_unshift($admins, \ProjectUGroup::PROJECT_ADMIN);
        }

        return $admins;
    }
}
