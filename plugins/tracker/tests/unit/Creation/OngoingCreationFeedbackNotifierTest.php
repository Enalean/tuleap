<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Response;
use Tracker_Migration_MigrationManager;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;

#[DisableReturnValueGenerationForTestDoubles]
final class OngoingCreationFeedbackNotifierTest extends TestCase
{
    private Tracker_Migration_MigrationManager&MockObject $tv3_migration_manager;
    private PendingJiraImportDao&MockObject $pending_jira_import_dao;
    private OngoingCreationFeedbackNotifier $feedback_notifier;
    private Project&MockObject $project;
    private Response&MockObject $response;

    protected function setUp(): void
    {
        $this->tv3_migration_manager   = $this->createMock(Tracker_Migration_MigrationManager::class);
        $this->pending_jira_import_dao = $this->createMock(PendingJiraImportDao::class);

        $this->project = $this->createMock(Project::class);
        $this->project->method('getId')->willReturn(42);
        $this->response = $this->createMock(Response::class);

        $this->feedback_notifier = new OngoingCreationFeedbackNotifier(
            $this->tv3_migration_manager,
            $this->pending_jira_import_dao
        );
    }

    public function testItDoesNotInformAnythingIfThereIsNoOngoingMigrations(): void
    {
        $this->tv3_migration_manager->expects($this->once())->method('thereAreMigrationsOngoingForProject')->willReturn(false);
        $this->pending_jira_import_dao->expects($this->once())->method('searchByProjectId')->willReturn([]);

        $this->response->expects(self::never())->method('addFeedback');

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }

    public function testItInformsTv3Migrations(): void
    {
        $this->tv3_migration_manager->expects($this->once())->method('thereAreMigrationsOngoingForProject')->willReturn(true);
        $this->pending_jira_import_dao->expects($this->once())->method('searchByProjectId')->willReturn([]);

        $this->project->expects($this->once())->method('getTruncatedEmailsUsage')->willReturn(false);

        $this->response->expects($this->once())->method('addFeedback')
            ->with('info', 'Some migrations are being processed. Your new trackers will appear as soon as the migrations are completed.');

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }

    public function testItInformsTv3MigrationsAndUntruncatedEmails(): void
    {
        $this->tv3_migration_manager->expects($this->once())->method('thereAreMigrationsOngoingForProject')->willReturn(true);
        $this->pending_jira_import_dao->expects($this->once())->method('searchByProjectId')->willReturn([]);

        $this->project->expects($this->once())->method('getTruncatedEmailsUsage')->willReturn(true);

        $this->response->expects(self::exactly(2))->method('addFeedback')
            ->with('info', self::callback(static fn(string $message) => in_array($message, [
                'Some migrations are being processed. Your new trackers will appear as soon as the migrations are completed.',
                'An email not truncated will be sent at the end of the migration process.',
            ])));

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }

    public function testItInformsPendingJiraImport(): void
    {
        $this->tv3_migration_manager->expects($this->once())->method('thereAreMigrationsOngoingForProject')->willReturn(false);
        $this->pending_jira_import_dao->expects($this->once())->method('searchByProjectId')
            ->willReturn([
                ['tracker_shortname' => 'bug'],
            ]);

        $this->response->expects($this->once())->method('addFeedback')
            ->with('info', 'A tracker creation from Jira is being processed for bug. Your new tracker will appear as soon as the import is completed.');

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }

    public function testItInformsManyPendingJiraImports(): void
    {
        $this->tv3_migration_manager->expects($this->once())->method('thereAreMigrationsOngoingForProject')->willReturn(false);
        $this->pending_jira_import_dao->expects($this->once())->method('searchByProjectId')
            ->willReturn([
                ['tracker_shortname' => 'bug'],
                ['tracker_shortname' => 'story'],
            ]);

        $this->response->expects($this->once())->method('addFeedback')
            ->with('info', 'Some tracker creations from Jira are being processed for bug, story. Your new trackers will appear as soon as the import is completed.');

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }
}
