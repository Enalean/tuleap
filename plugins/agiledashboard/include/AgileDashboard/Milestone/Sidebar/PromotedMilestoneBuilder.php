<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use DateTime;
use PFUser;
use Planning_ArtifactMilestone;
use PlanningFactory;
use Psr\Log\LoggerInterface;
use Tracker_Semantic_Title;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class PromotedMilestoneBuilder implements BuildPromotedMilestone
{
    public function __construct(
        private readonly PlanningFactory $planning_factory,
        private readonly SemanticTimeframeBuilder $timeframe_builder,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return Option<Planning_ArtifactMilestone>
     */
    public function build(Artifact $milestone_artifact, PFUser $user, \Project $project): Option
    {
        if (! $milestone_artifact->userCanView($user)) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }

        $title_field = Tracker_Semantic_Title::load($milestone_artifact->getTracker())->getField();
        if ($title_field === null) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }

        $timeframe = $this->timeframe_builder->getSemantic($milestone_artifact->getTracker());
        if (! $timeframe->isDefined()) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }
        $date_period  = $timeframe->getTimeframeCalculator()->buildDatePeriodWithoutWeekendForChangeset(
            $milestone_artifact->getLastChangeset(),
            $user,
            $this->logger
        );
        $current_date = (new DateTime())->getTimestamp();
        if (! ($date_period->getStartDate() <= $current_date && $date_period->getEndDate() >= $current_date)) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }

        $planning = $this->planning_factory->getPlanningByPlanningTracker($milestone_artifact->getTracker());
        if (! $planning) {
            return Option::nothing(Planning_ArtifactMilestone::class);
        }

        return Option::fromValue(new Planning_ArtifactMilestone(
            $project,
            $planning,
            $milestone_artifact,
        ));
    }
}
