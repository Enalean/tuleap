<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use EventManager;
use ForgeConfig;
use PermissionsManager;
use PFUser;
use ProjectHistoryDao;
use Tracker_Artifact;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactDao;
use Tracker_FormElement_Field_ComputedDaoCache;
use Tuleap\Project\XML\Export\ZipArchive;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;
use Tuleap\Tracker\RecentlyVisited\RecentlyVisitedDao;

class ArtifactDeletor
{
    const PROJECT_HISTORY_ARTIFACT_DELETED = 'tracker_artifact_delete';

    /**
     * @var Tracker_ArtifactDao
     */
    private $dao;
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
     * @var ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ArtifactWithTrackerStructureExporter
     */
    private $artifact_with_tracker_structure_exporter;
    /**
     * @var Tracker_FormElement_Field_ComputedDaoCache
     */
    private $computed_dao_cache;
    /**
     * @var RecentlyVisitedDao
     */
    private $recently_visited_dao;

    public function __construct(
        Tracker_ArtifactDao $dao,
        PermissionsManager $permissions_manager,
        CrossReferenceManager $cross_reference_manager,
        Tracker_Artifact_PriorityManager $tracker_artifact_priority_manager,
        ProjectHistoryDao $project_history_dao,
        EventManager $event_manager,
        ArtifactWithTrackerStructureExporter $artifact_with_tracker_structure_exporter,
        Tracker_FormElement_Field_ComputedDaoCache $computed_dao_cache,
        RecentlyVisitedDao $recently_visited_dao
    ) {
        $this->dao                                      = $dao;
        $this->permissions_manager                      = $permissions_manager;
        $this->cross_reference_manager                  = $cross_reference_manager;
        $this->tracker_artifact_priority_manager        = $tracker_artifact_priority_manager;
        $this->project_history_dao                      = $project_history_dao;
        $this->event_manager                            = $event_manager;
        $this->artifact_with_tracker_structure_exporter = $artifact_with_tracker_structure_exporter;
        $this->computed_dao_cache                       = $computed_dao_cache;
        $this->recently_visited_dao                     = $recently_visited_dao;
    }

    public function delete(Tracker_Artifact $artifact, PFUser $user)
    {
        $this->tryToArchiveArtifact($artifact, $user);
        $this->dao->startTransaction();
        $this->processDelete($artifact, $user);
        $this->dao->commit();
        $this->addProjectHistory($artifact);
        $this->processEvent($artifact);
    }

    public function deleteWithoutTransaction(Tracker_Artifact $artifact, PFUser $user)
    {
        $this->tryToArchiveArtifact($artifact, $user);
        $this->processDelete($artifact, $user);
        $this->addProjectHistory($artifact);
        $this->processEvent($artifact);
    }

    private function processDelete(Tracker_Artifact $artifact, PFUser $user)
    {
        foreach ($artifact->getChangesets() as $changeset) {
            $changeset->delete($user);
        }

        $this->permissions_manager->clearPermission(Tracker_Artifact::PERMISSION_ACCESS, $artifact->getId());
        $this->cross_reference_manager->deleteEntity($artifact->getId(), Tracker_Artifact::REFERENCE_NATURE, $artifact->getTracker()->getGroupId());
        $this->dao->deleteArtifactLinkReference($artifact->getId());
        $this->dao->deleteUnsubscribeNotificationForArtifact($artifact->getId());
        // We do not keep trace of the history change here because it doesn't have any sense
        $this->tracker_artifact_priority_manager->deletePriority($artifact);
        $this->computed_dao_cache->deleteAllArtifactCacheValues($artifact);
        $this->recently_visited_dao->deleteVisitByArtifactId($artifact->getId());
        $this->dao->delete($artifact->getId());
    }

    private function tryToArchiveArtifact(Tracker_Artifact $artifact, PFUser $user)
    {
        $archive_path = ForgeConfig::get('tmp_dir') . '/artifact_' . $artifact->getId() . '_'.time(). '.zip';
        $archive      = new ZipArchive($archive_path);
        $this->artifact_with_tracker_structure_exporter->exportArtifactAndTrackerStructureToXML($user, $artifact, $archive);
        $archive->close();
        $params = [
            'source_path'     => $archive->getArchivePath(),
            'archive_prefix'  => 'deleted_',
            'status'          => true,
            'error'           => null,
            'skip_duplicated' => false
        ];
        $this->event_manager->processEvent('archive_deleted_item', $params);
        if (file_exists($archive_path)) {
            unlink($archive_path);
        }
    }

    private function processEvent(Tracker_Artifact $artifact)
    {
        $this->event_manager->processEvent(
            TRACKER_EVENT_ARTIFACT_DELETE,
            array(
                'artifact' => $artifact,
            )
        );
    }

    private function addProjectHistory(Tracker_Artifact $artifact)
    {
        $this->project_history_dao->groupAddHistory(
            self::PROJECT_HISTORY_ARTIFACT_DELETED,
            '#' . $artifact->getId() . ' tracker #' . $artifact->getTrackerId() . ' (' . $artifact->getTracker()->getName() . ')',
            $artifact->getTracker()->getGroupId()
        );
    }
}
