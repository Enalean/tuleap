<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use PFUser;
use Planning_MilestoneFactory;
use Tracker;

class ArtifactCreatorChecker
{
    /**
     * @var Planning_MilestoneFactory
     */
    private $planning_milestone_factory;
    /**
     * @var MilestoneCreatorChecker
     */
    private $milestone_creator_checker;

    public function __construct(
        Planning_MilestoneFactory $planning_milestone_factory,
        MilestoneCreatorChecker $milestone_creator_checker
    ) {
        $this->planning_milestone_factory = $planning_milestone_factory;
        $this->milestone_creator_checker  = $milestone_creator_checker;
    }

    public function canCreateAnArtifact(PFUser $user, Tracker $tracker): bool
    {
        try {
            $virtual_top_milestone = $this->planning_milestone_factory->getVirtualTopMilestone($user, $tracker->getProject());
        } catch (\Planning_NoPlanningsException $e) {
            return true;
        }

        if ($virtual_top_milestone->getPlanning()->getPlanningTrackerId() !== $tracker->getId()) {
            return true;
        }

        return $this->milestone_creator_checker->canMilestoneBeCreated($virtual_top_milestone, $user);
    }
}
