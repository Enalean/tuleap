<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Ensure consistency of backlogs.
 *
 * When an element is added to a Plannification item, it must be add to Parents
 * plannification item as well
 *
 * Given I have following plannings
 * Epic -> Product
 * Epic -> Release
 *
 * And following hierarchy
 * Product -> Release (let say "Product Toto" and "Release 1.0")
 *
 * When I add a new Epic into "Release 1.0" backlog, it must be added into
 * "Product Toto" backlog as well
 */
class Planning_ArtifactLinker {
    private $artifact_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory) {
        $this->artifact_factory = $artifact_factory;
    }

    public function linkWithParents(Codendi_Request $request, Tracker_Artifact $artifact) {
        $user      = $request->getCurrentUser();
        $ancestors = $artifact->getAllAncestors($user);
        if (count($ancestors) == 0) {
            $artifact_id     = (int)$request->getValidated('link-artifact-id', 'uint', 0);
            $source_artifact = $this->artifact_factory->getArtifactById($artifact_id);
            if ($source_artifact) {
                foreach ($source_artifact->getAllAncestors($user) as $ancestor) {
                    $ancestor->linkArtifact($artifact->getId(), $user);
                }
            }
        }
    }
}

?>
