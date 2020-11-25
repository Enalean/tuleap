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
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;
use Tuleap\AgileDashboard\Planning\RootPlanning\DisplayTopPlanningAppEvent;
use Tuleap\layout\NewDropdown\NewDropdownLinkSectionPresenter;
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
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        AgileDashboard_PaneInfoIdentifier $pane_info_identifier,
        TrackerNewDropdownLinkPresenterBuilder $presenter_builder,
        HeaderOptionsForPlanningProvider $header_options_for_planning_provider,
        EventDispatcherInterface $event_dispatcher
    ) {
        $this->backlog_factory                      = $backlog_factory;
        $this->pane_info_identifier                 = $pane_info_identifier;
        $this->presenter_builder                    = $presenter_builder;
        $this->header_options_for_planning_provider = $header_options_for_planning_provider;
        $this->event_dispatcher                     = $event_dispatcher;
    }

    public function getHeaderOptions(PFUser $user, Planning_Milestone $milestone, string $identifier): array
    {
        $is_pane_a_planning_v2 = $this->pane_info_identifier->isPaneAPlanningV2($identifier);

        $header_options = [
            Layout::INCLUDE_FAT_COMBINED => ! $is_pane_a_planning_v2,
            'body_class'                 => ['agiledashboard-body']
        ];

        $this->createCurrentContextSectionWithBacklogTrackers($milestone, $user, $header_options);

        if ($is_pane_a_planning_v2) {
            $this->header_options_for_planning_provider->addPlanningOptions($user, $milestone, $header_options);
        }

        return $header_options;
    }

    private function createCurrentContextSectionWithBacklogTrackers(
        Planning_Milestone $milestone,
        PFUser $user,
        array &$header_options
    ): void {
        if ($milestone instanceof Planning_VirtualTopMilestone) {
            $this->createCurrentContextSectionForTopBacklog($milestone, $user, $header_options);
        } else {
            $this->createCurrentContextSectionFromTrackers(
                $this->backlog_factory->getBacklog($milestone)->getDescendantTrackers(),
                $user,
                (string) $milestone->getArtifactTitle(),
                $header_options
            );
        }
    }

    private function createCurrentContextSectionFromTrackers(
        array $trackers,
        PFUser $user,
        string $section_label,
        array &$header_options
    ): void {
        $links = [];
        foreach ($trackers as $tracker) {
            if ($tracker->userCanSubmitArtifact($user)) {
                $links[] = $this->presenter_builder->build($tracker);
            }
        }
        if (! empty($links)) {
            $header_options['new_dropdown_current_context_section'] = new NewDropdownLinkSectionPresenter(
                $section_label,
                $links,
            );
        }
    }

    private function createCurrentContextSectionForTopBacklog(
        Planning_VirtualTopMilestone $milestone,
        PFUser $user,
        array &$header_options
    ): void {
        $event = $this->event_dispatcher->dispatch(new DisplayTopPlanningAppEvent($milestone, $user));
        assert($event instanceof DisplayTopPlanningAppEvent);
        if (! $event->canBacklogItemsBeAdded()) {
            return;
        }
        $this->createCurrentContextSectionFromTrackers(
            $milestone->getPlanning()->getBacklogTrackers(),
            $user,
            dgettext('tuleap-agiledashboard', 'Top backlog'),
            $header_options
        );
    }
}
