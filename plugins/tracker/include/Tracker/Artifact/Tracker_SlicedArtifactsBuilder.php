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

class Tracker_SlicedArtifactsBuilder {

    /** @var Tracker_ArtifactDao */
    private $artifact_dao;

    public function __construct(Tracker_ArtifactDao $artifact_dao)
    {
        $this->artifact_dao = $artifact_dao;
    }

    public function getSlicedChildrenArtifactsForUser(Tracker_Artifact $artifact, PFUser $user, $limit, $offset)
    {
        $children            = array();
        $paginated_children  = $this->getPaginatedChildrenOfArtifact($artifact, $limit, $offset);
        $total_size          = $this->artifact_dao->foundRows();

        foreach ($paginated_children as $child) {
            if ($child->userCanView($user)) {
                $children[] = $child;
            }
        }

        return new Tracker_SlicedArtifacts(
            $children,
            $total_size
        );
    }

    private function getPaginatedChildrenOfArtifact(Tracker_Artifact $artifact, $limit, $offset)
    {
        return $this->artifact_dao->getPaginatedChildren($artifact->getId(), $limit, $offset)->instanciateWith(array($this->getArtifactFactory(), 'getInstanceFromRow'));
    }

    private function getArtifactFactory()
    {
        return Tracker_ArtifactFactory::instance();
    }
}
