<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use PermissionsManager;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactDao;
use Tuleap\Reference\CrossReferenceManager;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;

class ArtifactDependenciesDeletor
{
    public function __construct(
        private readonly PermissionsManager $permissions_manager,
        private readonly CrossReferenceManager $cross_reference_manager,
        private readonly Tracker_Artifact_PriorityManager $tracker_artifact_priority_manager,
        private readonly Tracker_ArtifactDao $dao,
        private readonly ComputedFieldDaoCache $computed_dao_cache,
        private readonly RecentlyVisitedDao $recently_visited_dao,
        private readonly PendingArtifactRemovalDao $artifact_removal,
    ) {
    }

    public function cleanDependencies(Artifact $artifact, DeletionContext $context): void
    {
        $artifact_deletor_visitor = new ArtifactFilesDeletorVisitor($artifact);
        $this->permissions_manager->clearPermission(Artifact::PERMISSION_ACCESS, $artifact->getId());
        $tracker = $artifact->getTracker();

        if (! $context->isAnArtifactMove()) {
            $this->cross_reference_manager->deleteEntity(
                (string) $artifact->getId(),
                Artifact::REFERENCE_NATURE,
                $tracker->getGroupId()
            );
        } else {
            $this->cross_reference_manager->deleteReferencesWhenArtifactIsSource(
                $artifact
            );

            if ($context->getSourceProjectId() !== $context->getDestinationProjectId()) {
                $this->cross_reference_manager->updateReferencesWhenArtifactIsInTarget($artifact, $context);
            }
        }

        foreach ($tracker->getFormElementFields() as $form_element) {
            $form_element->accept($artifact_deletor_visitor);
        }

        $this->dao->deleteArtifactLinkReference($artifact->getId());
        $this->dao->deleteUnsubscribeNotificationForArtifact($artifact->getId());
        // We do not keep trace of the history change here because it doesn't have any sense
        $this->tracker_artifact_priority_manager->deletePriority($artifact);
        $this->computed_dao_cache->deleteAllArtifactCacheValues($artifact);
        $this->recently_visited_dao->deleteVisitByArtifactId($artifact->getId());
        $this->artifact_removal->removeArtifact($artifact->getId());
    }
}
