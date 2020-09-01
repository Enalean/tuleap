<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\ProjectOwnership\ProjectOwner;

use Tuleap\Widget\Event\UserWithStarBadge;
use Tuleap\Widget\Event\UserWithStarBadgeCollector;

class UserWithStarBadgeFinder
{
    /** @var ProjectOwnerDAO */
    private $project_owner_dao;

    public function __construct(ProjectOwnerDAO $project_owner_dao)
    {
        $this->project_owner_dao = $project_owner_dao;
    }

    public function findBadgedUser(UserWithStarBadgeCollector $collector)
    {
        $project_owner_row = $this->project_owner_dao->searchByProjectID($collector->getProject()->getID());
        $badged_user_id = (string) $project_owner_row['user_id'];

        foreach ($collector->getUsers() as $user) {
            if ($user->getId() === $badged_user_id) {
                $collector->setUserWithStarBadge(
                    new UserWithStarBadge(
                        $user,
                        dgettext('tuleap-project_ownership', 'Owner'),
                        dgettext('tuleap-project_ownership', 'Project owner is accountable for project visibility, permissions & groups membership.')
                    )
                );
                return;
            }
        }
    }
}
