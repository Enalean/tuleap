<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\ExplicitBacklog\VerifyProjectUsesExplicitBacklog;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2Presenter;
use Tuleap\Kanban\SplitKanbanConfigurationChecker;
use Tuleap\Option\Option;

final class VirtualTopMilestonePresenterBuilder
{
    private const TOP_MILESTONE_DUMMY_ARTIFACT_ID = 'ABC';

    public function __construct(
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly VerifyProjectUsesExplicitBacklog $explicit_backlog_verifier,
        private readonly SplitKanbanConfigurationChecker $split_kanban_configuration_checker,
    ) {
    }

    /**
     * @param Option<\Planning_VirtualTopMilestone> $milestone
     */
    public function buildPresenter(Option $milestone, \Project $project, \PFUser $user): VirtualTopMilestonePresenter
    {
        $planning_presenter = $milestone->mapOr(function () use ($project, $user): ?PlanningV2Presenter {
            $additional_panes = $this->event_dispatcher->dispatch(new AllowedAdditionalPanesToDisplayCollector());

            $is_using_explicit_backlog = $this->explicit_backlog_verifier->isProjectUsingExplicitBacklog(
                (int) $project->getID()
            );

            return new PlanningV2Presenter(
                $user,
                $project,
                self::TOP_MILESTONE_DUMMY_ARTIFACT_ID,
                $is_using_explicit_backlog,
                $additional_panes->getIdentifiers(),
                $this->split_kanban_configuration_checker->isProjectAllowedToUseSplitKanban($project)
            );
        }, null);
        $is_project_admin   = $user->isAdmin((int) $project->getID());
        $backlog_title      = $this->split_kanban_configuration_checker->isProjectAllowedToUseSplitKanban($project) ? dgettext("tuleap-agiledashboard", "Backlog") : dgettext("tuleap-agiledashboard", "Top Backlog Planning");
        return new VirtualTopMilestonePresenter($planning_presenter, $is_project_admin, $backlog_title);
    }
}
