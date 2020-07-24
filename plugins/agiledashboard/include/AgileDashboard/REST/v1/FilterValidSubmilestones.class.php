<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use PFUser;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\IFilterValidElementsToUnkink;

class FilterValidSubmilestones implements IFilterValidElementsToUnkink
{

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    public function __construct(Planning_MilestoneFactory $milestone_factory, Planning_Milestone $milestone)
    {
        $this->milestone_factory = $milestone_factory;
        $this->milestone         = $milestone;
    }

    public function filter(PFUser $user, array $artifact_ids_to_be_removed): array
    {
        $submilestones = [];

        foreach ($artifact_ids_to_be_removed as $artifact_to_be_removed) {
            $candidate_submilestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, $artifact_to_be_removed);

            if ($candidate_submilestone && $this->milestone->milestoneCanBeSubmilestone($candidate_submilestone)) {
                $artifact_id = $candidate_submilestone->getArtifactId();
                if ($artifact_id) {
                    $submilestones[] = $artifact_id;
                }
            }
        }

        return $submilestones;
    }
}
