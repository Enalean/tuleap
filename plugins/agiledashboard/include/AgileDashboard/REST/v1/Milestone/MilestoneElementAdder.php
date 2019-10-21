<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use PFUser;
use Planning_Milestone;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\AgileDashboard\REST\v1\MilestoneResourceValidator;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use Tuleap\Tracker\REST\v1\ArtifactLinkUpdater;

class MilestoneElementAdder
{
    /**
     * @var ResourcesPatcher
     */
    private $resources_patcher;
    /**
     * @var MilestoneResourceValidator
     */
    private $milestone_validator;
    /**
     * @var ArtifactLinkUpdater
     */
    private $artifact_link_updater;

    public function __construct(
        ResourcesPatcher $resources_patcher,
        MilestoneResourceValidator $milestone_validator,
        ArtifactLinkUpdater $artifact_link_updater
    ) {
        $this->resources_patcher     = $resources_patcher;
        $this->milestone_validator   = $milestone_validator;
        $this->artifact_link_updater = $artifact_link_updater;
    }

    /**
     * @throws \Luracast\Restler\RestException
     * @throws \Tracker_NoArtifactLinkFieldException
     * @throws \Tuleap\AgileDashboard\REST\v1\ArtifactCannotBeInBacklogOfException
     * @throws \Tuleap\AgileDashboard\REST\v1\IdsFromBodyAreNotUniqueException
     */
    public function addElementToMilestone(PFUser $user, array $add, Planning_Milestone $milestone): array
    {
        $this->resources_patcher->startTransaction();

        $to_add = $this->resources_patcher->removeArtifactFromSource($user, $add);
        if (count($to_add)) {
            $valid_to_add = $this->milestone_validator->validateArtifactIdsCanBeAddedToBacklog(
                $to_add,
                $milestone,
                $user
            );
            $this->addMissingElementsToBacklog($milestone, $user, $valid_to_add);
        }
        $this->resources_patcher->commit();

        return $to_add;
    }

    /**
     * @throws \Tracker_NoArtifactLinkFieldException
     */
    private function addMissingElementsToBacklog(Planning_Milestone $milestone, PFUser $user, array $to_add): void
    {
        if (count($to_add) === 0) {
            return;
        }

        $this->artifact_link_updater->updateArtifactLinks(
            $user,
            $milestone->getArtifact(),
            $to_add,
            [],
            Tracker_FormElement_Field_ArtifactLink::NO_NATURE
        );
    }
}
