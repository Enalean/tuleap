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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_PaneInfoIdentifier;
use PFUser;
use Planning_Milestone;
use Planning_VirtualTopMilestone;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Option\Option;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

class HeaderOptionsProvider
{
    public function __construct(
        private readonly AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        private readonly AgileDashboard_PaneInfoIdentifier $pane_info_identifier,
        private readonly TrackerNewDropdownLinkPresenterBuilder $presenter_builder,
        private readonly HeaderOptionsForPlanningProvider $header_options_for_planning_provider,
        private readonly ParentTrackerRetriever $parent_tracker_retriever,
        private readonly CurrentContextSectionToHeaderOptionsInserter $header_options_inserter,
    ) {
    }

    /**
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    public function getCurrentContextSection(
        PFUser $user,
        Planning_Milestone $milestone,
        string $identifier,
    ): Option {
        $current_context_section = Option::nothing(NewDropdownLinkSectionPresenter::class);

        $is_pane_a_planning_v2 = $this->pane_info_identifier->isPaneAPlanningV2($identifier);

        $current_context_section = $is_pane_a_planning_v2
            ? $this->header_options_for_planning_provider->getCurrentContextSection($user, $milestone, $current_context_section)
            : $current_context_section;

        return $this->createCurrentContextSectionWithBacklogTrackers(
            $milestone,
            $user,
            $current_context_section,
            $identifier,
        );
    }

    /**
     * @param Option<NewDropdownLinkSectionPresenter> $current_context_section
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    private function createCurrentContextSectionWithBacklogTrackers(
        Planning_Milestone $milestone,
        PFUser $user,
        Option $current_context_section,
        string $pane_identifier,
    ): Option {
        if ($milestone instanceof Planning_VirtualTopMilestone) {
            return $this->createCurrentContextSectionForTopBacklog($milestone, $user, $current_context_section, $pane_identifier);
        }

        return $this->createCurrentContextSectionFromTrackers(
            $milestone,
            $this->backlog_factory->getBacklog($milestone)->getDescendantTrackers(),
            $user,
            (string) $milestone->getArtifactTitle(),
            $current_context_section,
            $pane_identifier
        );
    }

    /**
     * @param Option<NewDropdownLinkSectionPresenter> $current_context_section
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    private function createCurrentContextSectionFromTrackers(
        Planning_Milestone $milestone,
        array $trackers,
        PFUser $user,
        string $section_label,
        Option $current_context_section,
        string $pane_identifier,
    ): Option {
        $parent_trackers = $this->parent_tracker_retriever->getCreatableParentTrackers($milestone, $user, $trackers);
        foreach (array_merge($trackers, $parent_trackers) as $tracker) {
            if ($tracker->userCanSubmitArtifact($user)) {
                $current_context_section = $this->header_options_inserter->addLinkToCurrentContextSection(
                    $section_label,
                    $this->presenter_builder->buildWithAdditionalUrlParameters($tracker, [
                        'planning[' . $pane_identifier . '][' . $milestone->getPlanningId() . ']' => (string) $milestone->getArtifactId(),
                        \Planning_ArtifactLinker::LINK_TO_MILESTONE_PARAMETER => '1',
                    ]),
                    $current_context_section,
                );
            }
        }

        return $current_context_section;
    }

    /**
     * @param Option<NewDropdownLinkSectionPresenter> $current_context_section
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    private function createCurrentContextSectionForTopBacklog(
        Planning_VirtualTopMilestone $milestone,
        PFUser $user,
        Option $current_context_section,
        string $pane_identifier,
    ): Option {
        return $this->createCurrentContextSectionFromTrackers(
            $milestone,
            $milestone->getPlanning()->getBacklogTrackers(),
            $user,
            dgettext('tuleap-agiledashboard', 'Top backlog'),
            $current_context_section,
            $pane_identifier
        );
    }
}
