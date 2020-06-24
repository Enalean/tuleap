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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;

class OngoingCreationFeedbackNotifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Migration_MigrationManager
     */
    private $tv3_migration_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PendingJiraImportDao
     */
    private $pending_jira_import_dao;
    /**
     * @var OngoingCreationFeedbackNotifier
     */
    private $feedback_notifier;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Response
     */
    private $response;

    protected function setUp(): void
    {
        $this->tv3_migration_manager = Mockery::mock(\Tracker_Migration_MigrationManager::class);
        $this->pending_jira_import_dao = Mockery::mock(PendingJiraImportDao::class);

        $this->project = Mockery::mock(\Project::class)->shouldReceive(['getId' => 42])->getMock();
        $this->response = Mockery::mock(\Response::class);

        $this->feedback_notifier = new OngoingCreationFeedbackNotifier(
            $this->tv3_migration_manager,
            $this->pending_jira_import_dao
        );
    }

    public function testItDoesNotInformAnythingIfThereIsNoOngoingMigrations(): void
    {
        $this->tv3_migration_manager
            ->shouldReceive('thereAreMigrationsOngoingForProject')
            ->once()
            ->andReturn(false);

        $this->pending_jira_import_dao
            ->shouldReceive('searchByProjectId')
            ->once()
            ->andReturn([]);

        $this->response->shouldReceive('addFeedback')->never();

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }

    public function testItInformsTv3Migrations(): void
    {
        $this->tv3_migration_manager
            ->shouldReceive('thereAreMigrationsOngoingForProject')
            ->once()
            ->andReturn(true);

        $this->pending_jira_import_dao
            ->shouldReceive('searchByProjectId')
            ->once()
            ->andReturn([]);

        $this->project
            ->shouldReceive('getTruncatedEmailsUsage')
            ->once()
            ->andReturn(false);

        $this->response
            ->shouldReceive('addFeedback')
            ->with('info', 'Some migrations are being processed. Your new trackers will appear as soon as the migrations are completed.')
            ->once();

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }

    public function testItInformsTv3MigrationsAndUntruncatedEmails(): void
    {
        $this->tv3_migration_manager
            ->shouldReceive('thereAreMigrationsOngoingForProject')
            ->once()
            ->andReturn(true);

        $this->pending_jira_import_dao
            ->shouldReceive('searchByProjectId')
            ->once()
            ->andReturn([]);

        $this->project
            ->shouldReceive('getTruncatedEmailsUsage')
            ->once()
            ->andReturn(true);

        $this->response
            ->shouldReceive('addFeedback')
            ->with('info', 'Some migrations are being processed. Your new trackers will appear as soon as the migrations are completed.')
            ->once();
        $this->response
            ->shouldReceive('addFeedback')
            ->with('info', 'An email not truncated will be sent at the end of the migration process.')
            ->once();

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }

    public function testItInformsPendingJiraImport(): void
    {
        $this->tv3_migration_manager
            ->shouldReceive('thereAreMigrationsOngoingForProject')
            ->once()
            ->andReturn(false);

        $this->pending_jira_import_dao
            ->shouldReceive('searchByProjectId')
            ->once()
            ->andReturn([
                [
                    'tracker_shortname' => 'bug'
                ]
            ]);

        $this->response
            ->shouldReceive('addFeedback')
            ->with('info', 'A tracker creation from Jira is being processed for bug. Your new tracker will appear as soon as the import is completed.')
            ->once();

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }

    public function testItInformsManyPendingJiraImports(): void
    {
        $this->tv3_migration_manager
            ->shouldReceive('thereAreMigrationsOngoingForProject')
            ->once()
            ->andReturn(false);

        $this->pending_jira_import_dao
            ->shouldReceive('searchByProjectId')
            ->once()
            ->andReturn([
                [
                    'tracker_shortname' => 'bug'
                ],
                [
                    'tracker_shortname' => 'story'
                ],
            ]);

        $this->response
            ->shouldReceive('addFeedback')
            ->with('info', 'Some tracker creations from Jira are being processed for bug, story. Your new trackers will appear as soon as the import is completed.')
            ->once();

        $this->feedback_notifier->informUserOfOngoingMigrations($this->project, $this->response);
    }
}
