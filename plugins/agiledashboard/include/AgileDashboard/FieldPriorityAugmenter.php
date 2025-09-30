<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\Priority\SequenceIdManager;

class AgileDashboard_FieldPriorityAugmenter
{
    public function __construct(
        private readonly SequenceIdManager $sequence_id_manager,
        private readonly Planning_MilestoneFactory $milestone_factory,
    ) {
    }

    public function getAugmentedDataForFieldPriority(PFUser $user, Project $project, array $additional_criteria, int $artifact_id): ?int
    {
        return $this->getSequenceIdForFieldPriority($user, $project, $additional_criteria, $artifact_id);
    }

    private function getSequenceIdForFieldPriority(PFUser $user, Project $project, array $additional_criteria, int $artifact_id): ?int
    {
        $milestone = $this->getMilestoneFromCriteria($user, $project, $additional_criteria);

        if ($milestone !== null) {
            return $this->sequence_id_manager->getSequenceId($user, $milestone, $artifact_id);
        }

        return null;
    }

    private function getMilestoneFromCriteria(PFUser $user, Project $project, array $additional_criteria): ?Planning_Milestone
    {
        $milestone_provider = new AgileDashboard_Milestone_SelectedMilestoneProvider($additional_criteria, $this->milestone_factory, $user, $project);
        return $milestone_provider->getMilestone();
    }
}
