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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use Planning_Milestone;
use PFUser;
use Tuleap\Tracker\REST\Helpers\IdsFromBodyAreNotUniqueException;

class PatchAddContentValidator implements IValidateElementsToAdd
{
    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var MilestoneResourceValidator
     */
    private $milestone_validator;

    public function __construct(MilestoneResourceValidator $milestone_validator, Planning_Milestone $milestone, PFUser $user)
    {
        $this->milestone_validator = $milestone_validator;
        $this->milestone           = $milestone;
        $this->user                = $user;
    }

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws ArtifactDoesNotExistException
     * @throws ArtifactIsNotInBacklogTrackerException
     * @throws ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone
     */
    public function validate(array $to_add)
    {
        $this->milestone_validator->validateArtifactsFromBodyContentWithClosedItems($to_add, $this->milestone, $this->user);
    }
}
