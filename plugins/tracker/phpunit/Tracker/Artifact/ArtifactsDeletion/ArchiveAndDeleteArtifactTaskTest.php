<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBConnection;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;

final class ArchiveAndDeleteArtifactTaskTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testDBReconnection(): void
    {
        $artifact_exporter = \Mockery::mock(ArtifactWithTrackerStructureExporter::class);
        $artifact_deletor  = \Mockery::mock(ArtifactDependenciesDeletor::class);
        $event_manager     = \Mockery::mock(\EventManager::class);
        $db_connection     = \Mockery::mock(DBConnection::class);
        $logger            = \Mockery::mock(\Psr\Log\LoggerInterface::class);

        $task = new ArchiveAndDeleteArtifactTask($artifact_exporter, $artifact_deletor, $event_manager, $db_connection, $logger);

        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(10);
        $user = \Mockery::mock(\PFUser::class);

        $artifact_exporter->shouldReceive('exportArtifactAndTrackerStructureToXML');
        $event_manager->shouldReceive('processEvent');
        $artifact_deletor->shouldReceive('cleanDependencies');

        $db_connection->shouldReceive('reconnectAfterALongRunningProcess')->once();

        $task->archive($artifact, $user);
    }
}
