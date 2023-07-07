<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use PFUser;
use ProjectHistoryDao;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker_ArtifactDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactInstrumentation;
use Tuleap\Tracker\Artifact\Event\ArtifactDeleted;

class ArtifactDeletor
{
    public const PROJECT_HISTORY_ARTIFACT_DELETED = 'tracker_artifact_delete';

    public function __construct(
        private readonly Tracker_ArtifactDao $dao,
        private readonly ProjectHistoryDao $project_history_dao,
        private readonly PendingArtifactRemovalDao $pending_artifact_removal_dao,
        private readonly AsynchronousArtifactsDeletionActionsRunner $asynchronous_actions_runner,
        private readonly EventDispatcherInterface $event_manager,
    ) {
    }

    public function delete(Artifact $artifact, PFUser $user, DeletionContext $context): void
    {
        $this->dao->startTransaction();
        $this->processDelete($artifact, $user, $context);
        $this->dao->commit();
        ArtifactInstrumentation::increment(ArtifactInstrumentation::TYPE_DELETED);
        $this->addProjectHistory($artifact);
        $this->processEvent($artifact);
    }

    public function deleteWithoutTransaction(Artifact $artifact, PFUser $user, DeletionContext $context): void
    {
        $this->processDelete($artifact, $user, $context);
        $this->addProjectHistory($artifact);
        $this->processEvent($artifact);
    }

    private function processDelete(Artifact $artifact, PFUser $user, DeletionContext $context): void
    {
        $this->pending_artifact_removal_dao->addArtifactToPendingRemoval($artifact->getId());

        $this->asynchronous_actions_runner->executeArchiveAndArtifactDeletion($artifact, $user, $context);

        $this->dao->delete($artifact->getId());
    }

    private function addProjectHistory(Artifact $artifact): void
    {
        $this->project_history_dao->groupAddHistory(
            self::PROJECT_HISTORY_ARTIFACT_DELETED,
            '#' . $artifact->getId() . ' tracker #' . $artifact->getTrackerId() . ' (' . $artifact->getTracker()->getName() . ')',
            $artifact->getTracker()->getGroupId()
        );
    }

    private function processEvent(Artifact $artifact): void
    {
        $this->event_manager->dispatch(new ArtifactDeleted($artifact));
    }
}
