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

use CrossReferenceManager;
use PermissionsManager;
use Tracker_Artifact;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactDao;
use Tracker_FormElement_Field_ComputedDaoCache;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;

class ArtifactDependenciesDeletor
{
    /**
     * @var PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var CrossReferenceManager
     */
    private $cross_reference_manager;
    /**
     * @var Tracker_Artifact_PriorityManager
     */
    private $tracker_artifact_priority_manager;
    /**
     * @var Tracker_ArtifactDao
     */
    private $dao;
    /**
     * @var Tracker_FormElement_Field_ComputedDaoCache
     */
    private $computed_dao_cache;
    /**
     * @var RecentlyVisitedDao
     */
    private $recently_visited_dao;
    /**
     * @var PendingArtifactRemovalDao
     */
    private $artifact_removal;

    public function __construct(
        PermissionsManager $permissions_manager,
        CrossReferenceManager $cross_reference_manager,
        Tracker_Artifact_PriorityManager $tracker_artifact_priority_manager,
        Tracker_ArtifactDao $dao,
        Tracker_FormElement_Field_ComputedDaoCache $computed_dao_cache,
        RecentlyVisitedDao $recently_visited_dao,
        PendingArtifactRemovalDao $artifact_removal
    ) {
        $this->permissions_manager               = $permissions_manager;
        $this->cross_reference_manager           = $cross_reference_manager;
        $this->tracker_artifact_priority_manager = $tracker_artifact_priority_manager;
        $this->dao                               = $dao;
        $this->computed_dao_cache                = $computed_dao_cache;
        $this->recently_visited_dao              = $recently_visited_dao;
        $this->artifact_removal                  = $artifact_removal;
    }

    public function cleanDependencies(Tracker_Artifact $artifact)
    {
        $artifact_deletor_visitor = new ArtifactFilesDeletorVisitor($artifact);
        $this->permissions_manager->clearPermission(Tracker_Artifact::PERMISSION_ACCESS, $artifact->getId());
        $tracker = $artifact->getTracker();
        $this->cross_reference_manager->deleteEntity(
            $artifact->getId(),
            Tracker_Artifact::REFERENCE_NATURE,
            $tracker->getGroupId()
        );

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
