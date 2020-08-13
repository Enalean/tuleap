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
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\CancellationOfJiraImportNotifier;

class PendingJiraImportCleanerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PendingJiraImportBuilder
     */
    private $builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CancellationOfJiraImportNotifier
     */
    private $notifier;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PendingJiraImportDao
     */
    private $dao;
    /**
     * @var PendingJiraImportCleaner
     */
    private $cleaner;

    protected function setUp(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info');

        $this->notifier        = Mockery::mock(CancellationOfJiraImportNotifier::class);
        $this->dao             = Mockery::mock(PendingJiraImportDao::class);
        $this->builder         = Mockery::mock(PendingJiraImportBuilder::class);

        $this->cleaner = new PendingJiraImportCleaner(
            $logger,
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->builder,
            $this->notifier
        );
    }

    public function testDeleteDanglingPendingJiraImportsAfterOneDayAndNotifiesUsers(): void
    {
        $current_time       = new DateTimeImmutable('2020-05-13');
        $expected_timestamp = (new DateTimeImmutable('2020-05-12'))->getTimestamp();

        $jira_import_row_1 = $this->anExpiredImportRow('jira 1');
        $jira_import_row_2 = $this->anExpiredImportRow('jira 2');
        $this->dao->shouldReceive('searchExpiredImports')
            ->with($expected_timestamp)
            ->once()
            ->andReturn([
                $jira_import_row_1,
                $jira_import_row_2,
            ]);

        $jira_import_1 = Mockery::mock(PendingJiraImport::class);
        $jira_import_2 = Mockery::mock(PendingJiraImport::class);
        $this->builder
            ->shouldReceive('buildFromRow')
            ->with($jira_import_row_1)
            ->andReturn($jira_import_1);
        $this->builder
            ->shouldReceive('buildFromRow')
            ->with($jira_import_row_2)
            ->andReturn($jira_import_2);

        $this->dao->shouldReceive('deleteExpiredImports')
            ->with($expected_timestamp)
            ->once();

        $this->notifier
            ->shouldReceive('warnUserAboutDeletion')
            ->with($jira_import_1)
            ->once();
        $this->notifier
            ->shouldReceive('warnUserAboutDeletion')
            ->with($jira_import_2)
            ->once();

        $this->cleaner->deleteDanglingPendingJiraImports($current_time);
    }

    public function testItDoesNotWarnUserIfPendingJiraImportCannotBeBuilt(): void
    {
        $current_time       = new DateTimeImmutable('2020-05-13');
        $expected_timestamp = (new DateTimeImmutable('2020-05-12'))->getTimestamp();

        $jira_import_row = $this->anExpiredImportRow();
        $this->dao->shouldReceive('searchExpiredImports')
            ->with($expected_timestamp)
            ->once()
            ->andReturn([
                $jira_import_row,
            ]);

        $jira_import = Mockery::mock(PendingJiraImport::class);
        $this->builder
            ->shouldReceive('buildFromRow')
            ->with($jira_import_row)
            ->andThrow(UnableToBuildPendingJiraImportException::class);

        $this->dao->shouldReceive('deleteExpiredImports')
            ->with($expected_timestamp)
            ->once();

        $this->notifier
            ->shouldReceive('warnUserAboutDeletion')
            ->never();

        $this->cleaner->deleteDanglingPendingJiraImports($current_time);
    }

    /**
     * @return array
     */
    private function anExpiredImportRow(string $jira_project = 'jira project'): array
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
