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
use Psr\Log\LoggerInterface;
use PFUser;
use Tracker_Artifact;
use Tuleap\DB\DBConnection;
use Tuleap\Event\Events\ArchiveDeletedItemEvent;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;

class ArchiveAndDeleteArtifactTask
{
    /**
     * @var ArtifactWithTrackerStructureExporter
     */
    private $artifact_with_tracker_structure_exporter;
    /**
     * @var ArtifactDependenciesDeletor
     */
    private $dependencies_deletor;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var DBConnection
     */
    private $db_connection;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ArtifactWithTrackerStructureExporter $artifact_with_tracker_structure_exporter,
        ArtifactDependenciesDeletor $dependencies_deletor,
        EventManager $event_manager,
        DBConnection $db_connection,
        LoggerInterface $logger
    ) {
        $this->artifact_with_tracker_structure_exporter = $artifact_with_tracker_structure_exporter;
        $this->dependencies_deletor                     = $dependencies_deletor;
        $this->event_manager                            = $event_manager;
        $this->db_connection                            = $db_connection;
        $this->logger                                   = $logger;
    }

    public function archive(\Tracker_Artifact $artifact, \PFUser $user) : void
    {
        $this->tryToArchiveArtifact($artifact, $user);
        $this->dependencies_deletor->cleanDependencies($artifact);
    }

    private function tryToArchiveArtifact(Tracker_Artifact $artifact, PFUser $user) : void
    {
        $archive_file_provider = new ArchiveDeletedArtifactProvider(
            $this->artifact_with_tracker_structure_exporter,
            $artifact,
            $user
        );
        try {
            $this->event_manager->processEvent(new ArchiveDeletedItemEvent($archive_file_provider));
        } catch (\Exception $exception) {
            $this->logger->debug(
                "Unable to archive the artifact " . $artifact->getId() . ":" . $exception->getMessage()
            );
        } finally {
            $archive_file_provider->purge();
            $this->db_connection->reconnectAfterALongRunningProcess();
        }
    }
}
