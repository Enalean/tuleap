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
use Layout;
use PFUser;
use Planning_Milestone;
use Planning_VirtualTopMilestone;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;
use Tuleap\layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

class HeaderOptionsProvider
{
    /**
     * @var AgileDashboard_PaneInfoIdentifier
     */
    private $pane_info_identifier;
    /**
     * @var HeaderOptionsForPlanningProvider
     */
    private $header_options_for_planning_provider;
    /**
     * @var TrackerNewDropdownLinkPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory
     */
    private $backlog_factory;
    /**
     * @var ParentTrackerRetriever
     */
    private $parent_tracker_retriever;
    /**
     * @var CurrentContextSectionToHeaderOptionsInserter
     */
    private $header_options_inserter;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        AgileDashboard_PaneInfoIdentifier $pane_info_identifier,
        TrackerNewDropdownLinkPresenterBuilder $presenter_builder,
        HeaderOptionsForPlanningProvider $header_options_for_planning_provider,
        ParentTrackerRetriever $parent_tracker_retriever,
        CurrentContextSectionToHeaderOptionsInserter $header_options_inserter,
    ) {
        $this->backlog_factory                      = $backlog_factory;
        $this->pane_info_identifier                 = $pane_info_identifier;
        $this->presenter_builder                    = $presenter_builder;
        $this->header_options_for_planning_provider = $header_options_for_planning_provider;
        $this->parent_tracker_retriever             = $parent_tracker_retriever;
        $this->header_options_inserter              = $header_options_inserter;
    }

    public function getHeaderOptions(PFUser $user, Planning_Milestone $milestone, string $identifier): array
    {
        $is_pane_a_planning_v2 = $this->pane_info_identifier->isPaneAPlanningV2($identifier);

        $header_options = [
            Layout::INCLUDE_FAT_COMBINED => ! $is_pane_a_planning_v2,
            'body_class'                 => ['agiledashboard-body'],
        ];

        $this->createCurrentContextSectionWithBacklogTrackers($milestone, $user, $header_options, $identifier);

        if ($is_pane_a_planning_v2) {
            $this->header_options_for_planning_provider->addPlanningOptions($user, $milestone, $header_options);
        }

        return $header_options;
    }

    private function createCurrentContextSectionWithBacklogTrackers(
        Planning_Milestone $milestone,
        PFUser $user,
        array &$header_options,
        string $pane_identifier,
    ): void {
        if ($milestone instanceof Planning_VirtualTopMilestone) {
            $this->createCurrentContextSectionForTopBacklog($milestone, $user, $header_options, $pane_identifier);
        } else {
            $this->createCurrentContextSectionFromTrackers(
                $milestone,
                $this->backlog_factory->getBacklog($milestone)->getDescendantTrackers(),
                $user,
                (string) $milestone->getArtifactTitle(),
                $header_options,
                $pane_identifier
            );
        }
    }

    private function createCurrentContextSectionFromTrackers(
        Planning_Milestone $milestone,
        array $trackers,
        PFUser $user,
        string $section_label,
        array &$header_options,
        string $pane_identifier,
    ): void {
        $parent_trackers = $this->parent_tracker_retriever->getCreatableParentTrackers($milestone, $user, $trackers);
        foreach (array_merge($trackers, $parent_trackers) as $tracker) {
            if ($tracker->userCanSubmitArtifact($user)) {
                $this->header_options_inserter->addLinkToCurrentContextSection(
                    $section_label,
                    $this->presenter_builder->buildWithAdditionalUrlParameters($tracker, [
                        'planning[' . $pane_identifier . '][' . $milestone->getPlanningId() . ']' => (string) $milestone->getArtifactId(),
                        \Planning_ArtifactLinker::LINK_TO_MILESTONE_PARAMETER => '1',
                    ]),
                    $header_options
                );
            }
        }
    }

    private function createCurrentContextSectionForTopBacklog(
        Planning_VirtualTopMilestone $milestone,
        PFUser $user,
        array &$header_options,
        string $pane_identifier,
    ): void {
        $this->createCurrentContextSectionFromTrackers(
            $milestone,
            $milestone->getPlanning()->getBacklogTrackers(),
            $user,
            dgettext('tuleap-agiledashboard', 'Top backlog'),
            $header_options,
            $pane_identifier
        );
    }
}
