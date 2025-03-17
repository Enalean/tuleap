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
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\CancellationOfJiraImportNotifier;

#[DisableReturnValueGenerationForTestDoubles]
final class PendingJiraImportCleanerTest extends TestCase
{
    private PendingJiraImportBuilder&MockObject $builder;
    private CancellationOfJiraImportNotifier&MockObject $notifier;
    private PendingJiraImportDao&MockObject $dao;
    private PendingJiraImportCleaner $cleaner;

    protected function setUp(): void
    {
        $this->notifier = $this->createMock(CancellationOfJiraImportNotifier::class);
        $this->dao      = $this->createMock(PendingJiraImportDao::class);
        $this->builder  = $this->createMock(PendingJiraImportBuilder::class);

        $this->cleaner = new PendingJiraImportCleaner(
            new NullLogger(),
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
        $this->dao->expects(self::once())->method('searchExpiredImports')
            ->with($expected_timestamp)
            ->willReturn([$jira_import_row_1, $jira_import_row_2]);

        $jira_import_1 = $this->createStub(PendingJiraImport::class);
        $jira_import_2 = $this->createStub(PendingJiraImport::class);
        $this->builder->method('buildFromRow')->willReturnCallback(static fn(array $row) => match ($row) {
            $jira_import_row_1 => $jira_import_1,
            $jira_import_row_2 => $jira_import_2,
        });

        $this->dao->expects(self::once())->method('deleteExpiredImports')->with($expected_timestamp);

        $this->notifier->expects(self::exactly(2))->method('warnUserAboutDeletion')
            ->with(self::callback(static fn(PendingJiraImport $import) => in_array($import, [$jira_import_1, $jira_import_2])));

        $this->cleaner->deleteDanglingPendingJiraImports($current_time);
    }

    public function testItDoesNotWarnUserIfPendingJiraImportCannotBeBuilt(): void
    {
        $current_time       = new DateTimeImmutable('2020-05-13');
        $expected_timestamp = (new DateTimeImmutable('2020-05-12'))->getTimestamp();

        $jira_import_row = $this->anExpiredImportRow();
        $this->dao->expects(self::once())->method('searchExpiredImports')
            ->with($expected_timestamp)->willReturn([$jira_import_row]);

        $this->builder->method('buildFromRow')->with($jira_import_row)
            ->willThrowException(new UnableToBuildPendingJiraImportException());

        $this->dao->expects(self::once())->method('deleteExpiredImports')->with($expected_timestamp);

        $this->notifier->expects(self::never())->method('warnUserAboutDeletion');

        $this->cleaner->deleteDanglingPendingJiraImports($current_time);
    }

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
