<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST\v1\Cell;

use PFUser;
use Tracker_Artifact;
use Tuleap\REST\I18NRestException;

class AddValidator
{
    /**
     * @throws I18NRestException
     */
    public function validateArtifacts(
        Tracker_Artifact $swimlane_artifact,
        Tracker_Artifact $artifact_to_add,
        PFUser $current_user
    ): void {
        if (
            ! $this->isSoloItem($swimlane_artifact, $artifact_to_add) &&
            ! $this->isSwimlaneParentOfArtifactToAdd($swimlane_artifact, $artifact_to_add, $current_user)
        ) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-taskboard', "Artifact to add %d must be a child of swimlane %d."),
                    $artifact_to_add->getId(),
                    $swimlane_artifact->getId()
                )
            );
        }
    }

    private function isSoloItem(Tracker_Artifact $swimlane_artifact, Tracker_Artifact $artifact_to_add): bool
    {
        return $swimlane_artifact->getId() === $artifact_to_add->getId();
    }

    private function isSwimlaneParentOfArtifactToAdd(
        Tracker_Artifact $swimlane_artifact,
        Tracker_Artifact $artifact_to_add,
        PFUser $current_user
    ): bool {
        $parent_of_artifact_to_add = $artifact_to_add->getParent($current_user);
        return $parent_of_artifact_to_add !== null
            && $parent_of_artifact_to_add->getId() === $swimlane_artifact->getId();
    }
}
