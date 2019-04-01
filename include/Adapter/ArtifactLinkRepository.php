<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use PFUser;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;

class ArtifactLinkRepository
{
    /**
     * Find ids of all artifacts linked to a given artifact at a given date time (i.e. given change set).
     * @return int[]
     */
    public function findLinkedArtifactIds(
        PFUser $current_user,
        Tracker_Artifact_Changeset $changeset
    ): array {
        $artifact_link_field = $changeset->getArtifact()->getAnArtifactLinkField($current_user);
        if ($artifact_link_field === null) {
            return [];
        }

        $tracker_artifacts = $artifact_link_field->getLinkedArtifacts($changeset, $current_user);
        return array_map(
            function (Tracker_Artifact $tracker_artifact) {
                return $tracker_artifact->getId();
            },
            $tracker_artifacts
        );
    }
}
