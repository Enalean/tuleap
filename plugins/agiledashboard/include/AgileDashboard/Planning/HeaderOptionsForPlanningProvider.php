<?php
/**
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

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder;
use PFUser;
use Planning_Milestone;
use Planning_VirtualTopMilestone;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Option\Option;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

class HeaderOptionsForPlanningProvider
{
    /**
     * @var AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder
     */
    private $submilestone_finder;
    /**
     * @var TrackerNewDropdownLinkPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var CurrentContextSectionToHeaderOptionsInserter
     */
    private $header_options_inserter;

    public function __construct(
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder,
        TrackerNewDropdownLinkPresenterBuilder $presenter_builder,
        CurrentContextSectionToHeaderOptionsInserter $header_options_inserter,
    ) {
        $this->submilestone_finder     = $submilestone_finder;
        $this->presenter_builder       = $presenter_builder;
        $this->header_options_inserter = $header_options_inserter;
    }

    /**
     * @param Option<NewDropdownLinkSectionPresenter> $current_context_section
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    public function getCurrentContextSection(PFUser $user, Planning_Milestone $milestone, Option $current_context_section): Option
    {
        if ($milestone instanceof \Planning_NoMilestone) {
            return $current_context_section;
        }

        return $milestone instanceof Planning_VirtualTopMilestone
            ? $this->addPlanningOptionsForTopBacklog($milestone, $current_context_section)
            : $this->addPlanningOptionsForRegularMilestone($milestone, $user, $current_context_section);
    }

    /**
     * @param Option<NewDropdownLinkSectionPresenter> $current_context_section
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    private function addPlanningOptionsForRegularMilestone(
        Planning_Milestone $milestone,
        PFUser $user,
        Option $current_context_section,
    ): Option {
        $tracker = $this->submilestone_finder->findFirstSubmilestoneTracker($milestone);
        if (! $tracker || ! $tracker->userCanSubmitArtifact($user)) {
            return $current_context_section;
        }

        return $this->header_options_inserter->addLinkToCurrentContextSection(
            (string) $milestone->getArtifactTitle(),
            $this->presenter_builder->build($tracker),
            $current_context_section,
        );
    }

    /**
     * @param Option<NewDropdownLinkSectionPresenter> $current_context_section
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    private function addPlanningOptionsForTopBacklog(
        Planning_VirtualTopMilestone $top_milestone,
        Option $current_context_section,
    ): Option {
        return $this->header_options_inserter->addLinkToCurrentContextSection(
            dgettext('tuleap-agiledashboard', 'Top backlog'),
            $this->presenter_builder->build($top_milestone->getPlanning()->getPlanningTracker()),
            $current_context_section,
        );
    }
}
