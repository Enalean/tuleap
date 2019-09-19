<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class AgileDashboard_FieldPriorityAugmenter
{

    /** @var AgileDashboard_SequenceIdManager */
    private $sequence_id_manager;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    public function __construct(
        AgileDashboard_SequenceIdManager $sequence_id_manager,
        Planning_MilestoneFactory $milestone_factory
    ) {
        $this->sequence_id_manager = $sequence_id_manager;
        $this->milestone_factory   = $milestone_factory;
    }

    public function getAugmentedDataForFieldPriority(PFUser $user, Project $project, $additional_criteria, $artifact_id)
    {
        $sequence_id = $this->getSequenceIdForFieldPriority($user, $project, $additional_criteria, $artifact_id);

        if (! $sequence_id) {
            return;
        }

        return $sequence_id;
    }

    private function getSequenceIdForFieldPriority(PFUser $user, Project $project, $additional_criteria, $artifact_id)
    {
        $milestone = $this->getMilestoneFromCriteria($user, $project, $additional_criteria);

        if ($milestone) {
            return $this->sequence_id_manager->getSequenceId($user, $milestone, $artifact_id);
        }
    }

    private function getMilestoneFromCriteria(PFUser $user, Project $project, $additional_criteria)
    {
        $milestone_provider = new AgileDashboard_Milestone_SelectedMilestoneProvider($additional_criteria, $this->milestone_factory, $user, $project);
        return $milestone_provider->getMilestone();
    }
}
