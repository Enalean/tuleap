<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use ProjectUGroup;
use Tuleap\Notification\UgroupToBeNotifiedPresenter;
use User_ForgeUGroup;

final class CollectionOfUserGroupPresenterBuilder
{
    /**
     * @param User_ForgeUGroup[] $all_project_user_group
     * @param UgroupToBeNotifiedPresenter[] $notified_user_groups
     * @return list<array{id: int, name: string, selected: bool}>
     */
    public function getAllUserGroupsPresenter(array $all_project_user_group, array $notified_user_groups): array
    {
        $user_groups_presenter = [];
        foreach ($all_project_user_group as $user_group) {
            if ($user_group->getId() > 100 || in_array($user_group->getId(), [ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN], true)) {
                $user_groups_presenter[] = [
                    'id' => $user_group->getId(),
                    'name' => $user_group->getName(),
                    'selected' => array_any($notified_user_groups, static fn(UgroupToBeNotifiedPresenter $ugroup_to_be_notified_presenter) => $ugroup_to_be_notified_presenter->ugroup_id === $user_group->getId()),
                ];
            }
        }
        return $user_groups_presenter;
    }
}
