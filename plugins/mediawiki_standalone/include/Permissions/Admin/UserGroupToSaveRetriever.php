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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\Project\UGroupRetriever;

final class UserGroupToSaveRetriever
{
    public function __construct(private UGroupRetriever $ugroup_retriever)
    {
    }

    /**
     * @param int[] $readers_ugroup_ids
     *
     * @return \ProjectUGroup[]
     * @throws UnknownUserGroupException
     *
     */
    public function getUserGroups(\Project $project, array $readers_ugroup_ids): array
    {
        $user_groups = [];
        foreach ($readers_ugroup_ids as $ugroup_id) {
            $user_group = $this->ugroup_retriever->getUGroup($project, $ugroup_id);
            if (! $user_group) {
                throw new UnknownUserGroupException();
            }

            $user_groups[] = $user_group;
        }

        return $user_groups;
    }
}
