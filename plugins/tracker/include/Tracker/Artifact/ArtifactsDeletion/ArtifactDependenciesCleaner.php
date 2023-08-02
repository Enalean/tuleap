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
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;

class ArtifactDependenciesCleaner
{
    public function __construct(
        private readonly PermissionsManager $permissions_manager,
        private readonly Tracker_Artifact_PriorityManager $tracker_artifact_priority_manager,
        private readonly Tracker_ArtifactDao $dao,
        private readonly ComputedFieldDaoCache $computed_dao_cache,
        private readonly RecentlyVisitedDao $recently_visited_dao,
        private readonly PendingArtifactRemovalDao $artifact_removal,
        private readonly ArtifactChangesetValueDeletorDAO $changeset_value_deletor_DAO,
        private readonly PostArtifactMoveReferencesCleaner $post_artifact_move_references_cleaner,
        private readonly PostArtifactDeletionCleaner $artifact_deletion_cleaner,
    ) {
    }

    public function cleanDependencies(Artifact $artifact, DeletionContext $context, \PFUser $user): void
    {
        $this->permissions_manager->clearPermission(Artifact::PERMISSION_ACCESS, (string) $artifact->getId());

        $this->cleanReferences($artifact, $context, $user);

        $this->changeset_value_deletor_DAO->cleanAllChangesetValueInTransaction($artifact);
        $this->dao->deleteUnsubscribeNotificationForArtifact($artifact->getId());
        // We do not keep trace of the history change here because it doesn't have any sense
        $this->tracker_artifact_priority_manager->deletePriority($artifact);
        $this->computed_dao_cache->deleteAllArtifactCacheValues($artifact);
        $this->recently_visited_dao->deleteVisitByArtifactId($artifact->getId());
        $this->artifact_removal->removeArtifact($artifact->getId());
    }

    private function cleanReferences(Artifact $artifact, DeletionContext $context, \PFUser $user): void
    {
        if ($context->isAnArtifactMove()) {
            $this->post_artifact_move_references_cleaner->cleanReferencesAfterArtifactMove($artifact, $context, $user);

            return;
        }

        $this->artifact_deletion_cleaner->cleanReferencesAfterSimpleArtifactDeletion($artifact);
    }
}
