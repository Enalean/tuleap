<?php
/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Tuleap\Project\Admin\ProjectMembers\EnsureUserCanManageProjectMembers;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Project\ListOfProjectPresentersBuilder;
use Tuleap\Project\ProjectPresenter;

class InviteBuddiesPresenterBuilder
{
    public function __construct(
        private InvitationLimitChecker $limit_checker,
        private InviteBuddyConfiguration $invite_buddy_configuration,
        private ListOfProjectPresentersBuilder $project_presenters_builder,
        private EnsureUserCanManageProjectMembers $members_manager_checker,
    ) {
    }

    public function build(\PFUser $user, ?\Project $current_project): InviteBuddiesPresenter
    {
        $can_buddies_be_invited = $this->invite_buddy_configuration->canBuddiesBeInvited($user);
        $is_limit_reached       = $this->limit_checker->isLimitReached($user);
        $max_limit_by_day       = $this->invite_buddy_configuration->getNbMaxInvitationsByDay();

        $projects_presenters = array_reduce(
            $this->project_presenters_builder->getProjectPresenters($user),
            function (array $accumulator, ProjectPresenter $presenter) use ($user, $current_project): array {
                try {
                    $this->members_manager_checker->checkUserCanManageProjectMembers($user, $presenter->getProject());
                    $accumulator[] = new ProjectToBeInvitedIntoPresenter(
                        $presenter->id,
                        $presenter->icon,
                        $presenter->project_name,
                        $current_project && $presenter->id === (int) $current_project->getId(),
                    );
                } catch (UserIsNotAllowedToManageProjectMembersException) {
                    // skip the project if user cannot manage members
                }

                return $accumulator;
            },
            []
        );

        return new InviteBuddiesPresenter(
            $can_buddies_be_invited,
            $is_limit_reached,
            $max_limit_by_day,
            $projects_presenters
        );
    }
}
