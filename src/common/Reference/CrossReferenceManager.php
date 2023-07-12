<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use CrossReferenceDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionContext;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class CrossReferenceManager
{
    public function __construct(private readonly CrossReferenceDao $legacy_dao, private readonly CrossReferencesDao $cross_references_dao,)
    {
    }

    /**
     * Delete all cross references that with given entity as source or target.
     *
     * To be used when entity is deleted
     */
    public function deleteEntity(string $id, string $nature, int $project_id): bool
    {
        return $this->legacy_dao->deleteEntity($id, $nature, $project_id);
    }

    public function deleteReferencesWhenArtifactIsSource(Artifact $artifact): void
    {
        $this->cross_references_dao->deleteReferencesWhenArtifactIsSource(
            $artifact->getId(),
            Artifact::REFERENCE_NATURE,
            (int) $artifact->getTracker()->getGroupId()
        );
    }

    public function updateReferencesWhenArtifactIsInTarget(Artifact $artifact, DeletionContext $context): void
    {
        $this->cross_references_dao->updateReferencesWhenArtifactIsInTarget(
            $artifact->getId(),
            $context->getDestinationProjectId(),
        );
    }
}
