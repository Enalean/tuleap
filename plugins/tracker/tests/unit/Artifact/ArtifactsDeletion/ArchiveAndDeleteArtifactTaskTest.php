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

use EventManager;
use Psr\Log\NullLogger;
use Tuleap\DB\DBConnection;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\FormElement\FieldContentIndexer;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArchiveAndDeleteArtifactTaskTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testDBReconnection(): void
    {
        $artifact_exporter     = $this->createMock(ArtifactWithTrackerStructureExporter::class);
        $artifact_deletor      = $this->createMock(ArtifactDependenciesCleaner::class);
        $field_content_indexer = $this->createStub(FieldContentIndexer::class);
        $comments_indexer      = $this->createStub(ChangesetCommentIndexer::class);
        $event_manager         = $this->createMock(EventManager::class);
        $db_connection         = $this->createMock(DBConnection::class);
        $logger                = new NullLogger();

        $task = new ArchiveAndDeleteArtifactTask($artifact_exporter, $artifact_deletor, $field_content_indexer, $comments_indexer, $event_manager, $db_connection, $logger);

        $artifact = ArtifactTestBuilder::anArtifact(10)->build();
        $user     = UserTestBuilder::anActiveUser()->build();

        $artifact_exporter->method('exportArtifactAndTrackerStructureToXML');
        $event_manager->method('processEvent');
        $artifact_deletor->method('cleanDependencies');
        $field_content_indexer->method('askForDeletionOfIndexedFieldsFromArtifact');
        $comments_indexer->method('askForDeletionOfIndexedCommentsFromArtifact');

        $db_connection->expects(self::once())->method('reconnectAfterALongRunningProcess');

        $project_id = 102;
        $task->archive($artifact, $user, DeletionContext::regularDeletion($project_id));
    }
}
