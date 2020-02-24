<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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

use Tracker_FormElement_Field_ArtifactLink;
use Tracker_ArtifactFactory;
use Planning_Milestone;
use PFUser;
use Tuleap\Tracker\REST\v1\ArtifactLinkUpdater;

class MilestoneContentUpdater
{
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    public function __construct(ArtifactLinkUpdater $artifactlink_updater)
    {
        $this->artifact_factory         = Tracker_ArtifactFactory::instance();
        $this->artifactlink_updater     = $artifactlink_updater;
    }

    /**
     * User want to update the content of a given milestone
     *
     * @param array              $linked_artifact_ids  The ids of the artifacts to link
     * @param PFUser             $current_user         The user who made the link
     * @param Planning_Milestone $milestone            The milestone
     *
     */
    public function updateMilestoneContent(array $linked_artifact_ids, PFUser $current_user, Planning_Milestone $milestone)
    {
        $this->artifactlink_updater->update(
            $linked_artifact_ids,
            $milestone->getArtifact(),
            $current_user,
            new FilterValidContent(
                $this->artifact_factory,
                $milestone
            ),
            Tracker_FormElement_Field_ArtifactLink::NO_NATURE
        );
    }

    public function appendElementToMilestoneBacklog($linked_artifact_id, PFUser $current_user, Planning_Milestone $milestone)
    {
        $linked_artifact_ids = $this->artifactlink_updater->getElementsAlreadyLinkedToArtifact(
            $milestone->getArtifact(),
            $current_user
        );

        array_push($linked_artifact_ids, $linked_artifact_id);

        $this->updateMilestoneContent(array_unique($linked_artifact_ids), $current_user, $milestone);
    }
}
