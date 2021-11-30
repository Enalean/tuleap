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
use Tuleap\layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
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

    public function addPlanningOptions(PFUser $user, Planning_Milestone $milestone, array &$header_options): void
    {
        if (! isset($header_options['body_class'])) {
            $header_options['body_class'] = [];
        }

        $header_options['body_class'][] = 'has-sidebar-with-pinned-header';

        if ($milestone instanceof \Planning_NoMilestone) {
            return;
        }

        if ($milestone instanceof Planning_VirtualTopMilestone) {
            $this->addPlanningOptionsForTopBacklog($milestone, $user, $header_options);
        } else {
            $this->addPlanningOptionsForRegularMilestone($milestone, $user, $header_options);
        }
    }

    private function addPlanningOptionsForRegularMilestone(
        Planning_Milestone $milestone,
        PFUser $user,
        array &$header_options,
    ): void {
        $tracker = $this->submilestone_finder->findFirstSubmilestoneTracker($milestone);
        if (! $tracker || ! $tracker->userCanSubmitArtifact($user)) {
            return;
        }

        $this->header_options_inserter->addLinkToCurrentContextSection(
            (string) $milestone->getArtifactTitle(),
            $this->presenter_builder->build($tracker),
            $header_options,
        );
    }

    private function addPlanningOptionsForTopBacklog(
        Planning_VirtualTopMilestone $top_milestone,
        PFUser $user,
        array &$header_options,
    ): void {
        $this->header_options_inserter->addLinkToCurrentContextSection(
            dgettext('tuleap-agiledashboard', 'Top backlog'),
            $this->presenter_builder->build($top_milestone->getPlanning()->getPlanningTracker()),
            $header_options,
        );
    }
}
