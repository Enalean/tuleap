<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use Tracker;
use Tuleap\AgileDashboard\Semantic\SemanticDone;

class MessageFetcher
{
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var AgileDashboard_Semantic_InitialEffortFactory
     */
    private $initial_effort_factory;

    public function __construct(
        PlanningFactory $planning_factory,
        AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory
    ) {
        $this->planning_factory       = $planning_factory;
        $this->initial_effort_factory = $initial_effort_factory;
    }

    /**
     * @return array
     */
    public function getWarningsRelatedToPlanningConfiguration(Tracker $tracker)
    {
        $warnings = array();
        $planning = $this->planning_factory->getPlanningByPlanningTracker($tracker);

        if (! $planning) {
            $warnings[] = "<li>" . dgettext('tuleap-agiledashboard', 'This tracker is not a planning tracker') . "</li>";
            return $warnings;
        }

        foreach ($planning->getBacklogTrackers() as $backlog_tracker) {
            $backlog_tracker_name = $backlog_tracker->getName();

            $done_semantic = SemanticDone::load($backlog_tracker);
            if (! $done_semantic->isSemanticDefined()) {
                $warnings[] = "<li>".
                    sprintf(dgettext('tuleap-agiledashboard', 'Semantic done is not defined for tracker %s'), $backlog_tracker_name).
                    "</li>";
            }

            $initial_effort_semantic = $this->initial_effort_factory->getByTracker($tracker);
            if (! $initial_effort_semantic->getField()) {
                $warnings[] = "<li>".
                    sprintf(dgettext('tuleap-agiledashboard', 'Semantic initial effort is not defined for tracker %s'), $backlog_tracker_name).
                    "</li>";
            }
        }

        return $warnings;
    }
}
