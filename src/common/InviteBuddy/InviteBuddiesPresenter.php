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

use EventManager;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Project\ListOfProjectPresentersBuilder;
use Tuleap\Project\ProjectPresenter;

/**
 * @psalm-immutable
 */
class InviteBuddiesPresenter
{
    public readonly string $instance_name;
    public readonly bool $has_projects;

    /**
     * @param ProjectToBeInvitedIntoPresenter[] $projects
     */
    public function __construct(
        public readonly bool $can_buddies_be_invited,
        public readonly bool $is_limit_reached,
        public readonly int $max_limit_by_day,
        public readonly array $projects,
    ) {
        $this->instance_name = (string) \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME);
        $this->has_projects  = count($this->projects) > 0;
    }

    public static function build(\PFUser $user, ?\Project $current_project, ListOfProjectPresentersBuilder $project_presenters_builder): self
    {
        $event_manager              = \EventManager::instance();
        $limit_checker              = new InvitationLimitChecker(
            new InvitationDao(new SplitTokenVerificationStringHasher()),
            new InviteBuddyConfiguration($event_manager)
        );
        $invite_buddy_configuration = new InviteBuddyConfiguration(EventManager::instance());
        $can_buddies_be_invited     = $invite_buddy_configuration->canBuddiesBeInvited($user);
        $is_limit_reached           = $limit_checker->isLimitReached($user);
        $max_limit_by_day           = $invite_buddy_configuration->getNbMaxInvitationsByDay();

        $projects_presenters = array_reduce(
            $project_presenters_builder->getProjectPresenters($user),
            static function (array $accumulator, ProjectPresenter $presenter) use ($current_project): array {
                if ($presenter->is_current_user_admin) {
                    $accumulator[] = new ProjectToBeInvitedIntoPresenter(
                        $presenter->id,
                        $presenter->icon,
                        $presenter->project_name,
                        $current_project && $presenter->id === (int) $current_project->getId(),
                    );
                }

                return $accumulator;
            },
            []
        );

        return new self($can_buddies_be_invited, $is_limit_reached, $max_limit_by_day, $projects_presenters);
    }
}
