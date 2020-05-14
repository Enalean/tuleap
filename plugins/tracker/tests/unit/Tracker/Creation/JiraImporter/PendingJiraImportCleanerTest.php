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

namespace Tuleap\Tracker\Creation\JiraImporter;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Psr\Log\LoggerInterface;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use UserManager;

class PendingJiraImportCleanerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $project_manager;
    private $user_manager;
    private $user;
    private $project;
    private $dao;
    private $notifier;
    private $cleaner;

    protected function setUp(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info');

        $this->notifier        = Mockery::mock(CancellationOfJiraImportNotifier::class);
        $this->dao             = Mockery::mock(PendingJiraImportDao::class);
        $this->project         = Mockery::mock(Project::class);
        $this->user            = Mockery::mock(PFUser::class);
        $this->user_manager    = Mockery::mock(UserManager::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);

        $this->cleaner = new PendingJiraImportCleaner(
            $logger,
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->project_manager,
            $this->user_manager,
            $this->notifier
        );
    }

    public function testDeleteDanglingPendingJiraImportsAfterOneDayAndNotifiesUsers(): void
    {
        $current_time       = new DateTimeImmutable('2020-05-13');
        $expected_timestamp = (new DateTimeImmutable('2020-05-12'))->getTimestamp();

        $this->dao->shouldReceive('searchExpiredImports')
            ->with($expected_timestamp)
            ->once()
            ->andReturn([
                $this->anExpiredImport('jira 1'),
                $this->anExpiredImport('jira 2')
            ]);

        $this->dao->shouldReceive('deleteExpiredImports')
            ->with($expected_timestamp)
            ->once();

        $this->project->shouldReceive(
            [
                'isError'  => false,
                'isActive' => true
            ]
        );
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $this->user->shouldReceive(
            [
                'isAlive' => true,
            ]
        );
        $this->user_manager->shouldReceive('getUserById')->andReturn($this->user);

        $this->notifier
            ->shouldReceive('warnUserAboutDeletion')
            ->with(Mockery::on(function (PendingJiraImport $import) {
                return $import->getJiraProjectId() === 'jira 1';
            }))
            ->once();
        $this->notifier
            ->shouldReceive('warnUserAboutDeletion')
            ->with(Mockery::on(function (PendingJiraImport $import) {
                return $import->getJiraProjectId() === 'jira 2';
            }))
            ->once();

        $this->cleaner->deleteDanglingPendingJiraImports($current_time);
    }

    public function testItDoesNotWarnUserIfProjectIsNotActive(): void
    {
        $current_time       = new DateTimeImmutable('2020-05-13');
        $expected_timestamp = (new DateTimeImmutable('2020-05-12'))->getTimestamp();

        $this->dao->shouldReceive('searchExpiredImports')
            ->with($expected_timestamp)
            ->once()
            ->andReturn([$this->anExpiredImport()]);

        $this->dao->shouldReceive('deleteExpiredImports')
            ->with($expected_timestamp)
            ->once();

        $this->project->shouldReceive(
            [
                'isError'  => false,
                'isActive' => false
            ]
        );
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $this->user->shouldReceive(
            [
                'isAlive' => true,
            ]
        );
        $this->user_manager->shouldReceive('getUserById')->andReturn($this->user);

        $this->notifier
            ->shouldReceive('warnUserAboutDeletion')
            ->never();

        $this->cleaner->deleteDanglingPendingJiraImports($current_time);
    }

    public function testItDoesNotWarnUserIfProjectIsNotValid(): void
    {
        $current_time       = new DateTimeImmutable('2020-05-13');
        $expected_timestamp = (new DateTimeImmutable('2020-05-12'))->getTimestamp();

        $this->dao->shouldReceive('searchExpiredImports')
            ->with($expected_timestamp)
            ->once()
            ->andReturn([$this->anExpiredImport()]);

        $this->dao->shouldReceive('deleteExpiredImports')
            ->with($expected_timestamp)
            ->once();

        $this->project->shouldReceive(
            [
                'isError'  => true,
                'isActive' => true
            ]
        );
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $this->user->shouldReceive(
            [
                'isAlive' => true,
            ]
        );
        $this->user_manager->shouldReceive('getUserById')->andReturn($this->user);

        $this->notifier
            ->shouldReceive('warnUserAboutDeletion')
            ->never();

        $this->cleaner->deleteDanglingPendingJiraImports($current_time);
    }

    public function testItDoesNotWarnUserIfUserIsNotAlive(): void
    {
        $current_time       = new DateTimeImmutable('2020-05-13');
        $expected_timestamp = (new DateTimeImmutable('2020-05-12'))->getTimestamp();

        $this->dao->shouldReceive('searchExpiredImports')
            ->with($expected_timestamp)
            ->once()
            ->andReturn([$this->anExpiredImport()]);

        $this->dao->shouldReceive('deleteExpiredImports')
            ->with($expected_timestamp)
            ->once();

        $this->project->shouldReceive(
            [
                'isError'  => false,
                'isActive' => true
            ]
        );
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $this->user->shouldReceive(
            [
                'isAlive' => false,
            ]
        );
        $this->user_manager->shouldReceive('getUserById')->andReturn($this->user);

        $this->notifier
            ->shouldReceive('warnUserAboutDeletion')
            ->never();

        $this->cleaner->deleteDanglingPendingJiraImports($current_time);
    }

    /**
     * @return array
     */
    private function anExpiredImport(string $jira_project = 'jira project'): array
    {
        return [
            'created_on'           => 0,
            'jira_server'          => '',
            'jira_issue_type_name' => '',
            'tracker_name'         => '',
            'tracker_shortname'    => '',
            'project_id'           => 42,
            'user_id'              => 103,
            'jira_project_id'      => $jira_project,
        ];
    }
}
