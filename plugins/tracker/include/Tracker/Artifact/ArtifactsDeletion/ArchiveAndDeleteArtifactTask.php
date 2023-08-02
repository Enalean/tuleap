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

use EventManager;
use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\DB\DBConnection;
use Tuleap\Event\Events\ArchiveDeletedItemEvent;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\FormElement\FieldContentIndexer;

class ArchiveAndDeleteArtifactTask
{
    public function __construct(
        private readonly ArtifactWithTrackerStructureExporter $artifact_with_tracker_structure_exporter,
        private readonly ArtifactDependenciesCleaner $dependencies_cleaner,
        private readonly FieldContentIndexer $field_content_indexer,
        private readonly ChangesetCommentIndexer $changeset_comment_indexer,
        private readonly EventManager $event_manager,
        private readonly DBConnection $db_connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function archive(\Tuleap\Tracker\Artifact\Artifact $artifact, \PFUser $user, DeletionContext $context): void
    {
        $this->tryToArchiveArtifact($artifact, $user);
        $this->dependencies_cleaner->cleanDependencies($artifact, $context, $user);
        $this->field_content_indexer->askForDeletionOfIndexedFieldsFromArtifact($artifact);
        $this->changeset_comment_indexer->askForDeletionOfIndexedCommentsFromArtifact($artifact);
    }

    private function tryToArchiveArtifact(Artifact $artifact, PFUser $user): void
    {
        $archive_file_provider = new ArchiveDeletedArtifactProvider(
            $this->artifact_with_tracker_structure_exporter,
            $artifact,
            $user
        );
        try {
            $this->event_manager->processEvent(new ArchiveDeletedItemEvent($archive_file_provider));
        } catch (\Exception $exception) {
            $this->logger->error(
                "Unable to archive the artifact " . $artifact->getId() . ":" . $exception->getMessage(),
                ['exception' => $exception]
            );
        } finally {
            $archive_file_provider->purge();
            $this->db_connection->reconnectAfterALongRunningProcess();
        }
    }
}
