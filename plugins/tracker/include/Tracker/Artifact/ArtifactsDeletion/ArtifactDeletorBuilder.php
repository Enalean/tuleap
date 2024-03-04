<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use BackendLogger;
use EventManager;
use ProjectHistoryDao;
use Tracker_ArtifactDao;
use Tuleap\DB\ThereIsAnOngoingTransactionChecker;
use Tuleap\Queue\ForceSynchronousMode;
use Tuleap\Queue\IsAsyncTaskProcessingAvailable;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerAvailability;
use WrapperLogger;

class ArtifactDeletorBuilder
{
    public static function build(): ArtifactDeletor
    {
        return self::buildDeletor(new WorkerAvailability());
    }

    public static function buildForcedSynchronousDeletor(): ArtifactDeletor
    {
        return self::buildDeletor(new ForceSynchronousMode());
    }

    private static function buildDeletor(IsAsyncTaskProcessingAvailable $task_processing_available): ArtifactDeletor
    {
        $logger = new WrapperLogger(BackendLogger::getDefaultLogger(), self::class);

        $async_artifact_archive_runner = new AsynchronousArtifactsDeletionActionsRunner(
            new PendingArtifactRemovalDao(),
            $logger,
            \UserManager::instance(),
            new QueueFactory($logger, new ThereIsAnOngoingTransactionChecker()),
            $task_processing_available,
            new ArchiveAndDeleteArtifactTaskBuilder()
        );

        return new ArtifactDeletor(
            new Tracker_ArtifactDao(),
            new ProjectHistoryDao(),
            new PendingArtifactRemovalDao(),
            $async_artifact_archive_runner,
            EventManager::instance()
        );
    }
}
