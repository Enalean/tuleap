<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use AgileDashboard_Semantic_InitialEffortFactory;
use PlanningFactory;
use Tuleap\Tracker\FormElement\ChartConfigurationWarning;
use Tuleap\Tracker\FormElement\ChartConfigurationWarningLink;
use Tuleap\Tracker\FormElement\ChartConfigurationWarningWithLinks;
use Tuleap\Tracker\FormElement\Event\ExternalTrackerChartConfigurationWarningMessage;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;

final readonly class MessageFetcher
{
    public function __construct(
        private PlanningFactory $planning_factory,
        private AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory,
        private SemanticDoneFactory $semantic_done_factory,
    ) {
    }

    public function collectWarningsRelatedToPlanningConfiguration(ExternalTrackerChartConfigurationWarningMessage $event): void
    {
        $planning = $this->planning_factory->getPlanningByPlanningTracker($event->user, $event->field->getTracker());
        if (! $planning) {
            $event->warnings->addWarning(
                ChartConfigurationWarning::fromMessage(
                    dgettext(
                        'tuleap-agiledashboard',
                        'This tracker is not a planning tracker'
                    ),
                ),
            );
            return;
        }

        foreach ($planning->getBacklogTrackers() as $backlog_tracker) {
            $backlog_tracker_name = $backlog_tracker->getName();

            $done_semantic = $this->semantic_done_factory->getInstanceByTracker($backlog_tracker);
            if (! $done_semantic->isSemanticDefined()) {
                $event->warnings->addWarning(
                    ChartConfigurationWarningWithLinks::fromMessageAndLinks(
                        dgettext(
                            'tuleap-agiledashboard',
                            'Semantic done is not defined for tracker:'
                        ),
                        new ChartConfigurationWarningLink($done_semantic->getUrl(), $backlog_tracker_name),
                    )
                );
            }

            $initial_effort_semantic = $this->initial_effort_factory->getByTracker($backlog_tracker);
            if (! $initial_effort_semantic->getField()) {
                $event->warnings->addWarning(
                    ChartConfigurationWarningWithLinks::fromMessageAndLinks(
                        dgettext(
                            'tuleap-agiledashboard',
                            'Semantic initial effort is not defined for tracker:'
                        ),
                        new ChartConfigurationWarningLink($initial_effort_semantic->getUrl(), $backlog_tracker_name),
                    )
                );
            }
        }
    }
}
