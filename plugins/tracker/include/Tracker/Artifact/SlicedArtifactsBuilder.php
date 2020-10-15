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
declare(strict_types=1);

namespace Tuleap\Tracker\Artifact;

use IProvideDataAccessResult;
use PFUser;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;

class SlicedArtifactsBuilder
{
    /**
     * @var Tracker_ArtifactDao
     */
    private $artifact_dao;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(Tracker_ArtifactDao $artifact_dao, Tracker_ArtifactFactory $artifact_factory)
    {
        $this->artifact_dao     = $artifact_dao;
        $this->artifact_factory = $artifact_factory;
    }

    public function getSlicedChildrenArtifactsForUser(Artifact $artifact, PFUser $user, $limit, $offset): SlicedArtifacts
    {
        $children           = [];
        $paginated_children = $this->getPaginatedChildrenOfArtifact($artifact, $limit, $offset);
        $total_size         = (int) $this->artifact_dao->foundRows();

        foreach ($paginated_children as $row) {
            $child = $this->artifact_factory->getInstanceFromRow($row);
            if ($child->userCanView($user)) {
                $children[] = new RankedArtifact($child, (int) $row['rank']);
            }
        }

        return new SlicedArtifacts(
            $children,
            $total_size
        );
    }

    private function getPaginatedChildrenOfArtifact(Artifact $artifact, $limit, $offset): IProvideDataAccessResult
    {
        return $this->artifact_dao->getPaginatedChildren($artifact->getId(), $limit, $offset);
    }
}
