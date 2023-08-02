<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Reference\CrossReferenceManager;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinkWithNoType;
use Tuleap\Tracker\Artifact\Link\ArtifactLinker;
use Tuleap\Tracker\Artifact\RetrieveArtifact;

class PostArtifactMoveReferencesCleaner
{
    public function __construct(
        private readonly CrossReferenceManager $cross_reference_manager,
        private readonly RetrieveReverseLinks $reverse_links_retriever,
        private readonly ArtifactLinker $artifact_linker,
        private readonly RetrieveArtifact $artifact_factory,
    ) {
    }

    public function cleanReferencesAfterArtifactMove(Artifact $artifact, DeletionContext $context, \PFUser $user): void
    {
        $this->cross_reference_manager->deleteReferencesWhenArtifactIsSource(
            $artifact
        );

        if ($context->getSourceProjectId() !== $context->getDestinationProjectId()) {
            $this->cross_reference_manager->updateReferencesWhenArtifactIsInTarget($artifact, $context);

            $reverse_link_collection = $this->reverse_links_retriever->retrieveReverseLinks($artifact, $user);
            foreach ($reverse_link_collection->links as $reverse_link) {
                if ($reverse_link->getType() !== Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD) {
                    continue;
                }

                $reverse_artifact = $this->artifact_factory->getArtifactById($reverse_link->getSourceArtifactId());
                if (! $reverse_artifact) {
                    continue;
                }
                $link_to_update = CollectionOfForwardLinks::fromReverseLink(
                    $artifact,
                    ReverseLinkWithNoType::fromReverseLink($reverse_link)
                );

                $this->artifact_linker->linkArtifact(
                    $reverse_artifact,
                    $link_to_update,
                    $user
                );
            }
        }
    }
}
