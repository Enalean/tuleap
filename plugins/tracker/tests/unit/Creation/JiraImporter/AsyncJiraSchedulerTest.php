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

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class AsyncJiraSchedulerTest extends TestCase
{
    public function testScheduleCreationStoreJiraInformationWithEncryptedToken(): void
    {
        $pending_jira_import_dao = $this->createMock(PendingJiraImportDao::class);
        $uuid                    = new UUIDTestContext();
        $pending_jira_import_dao->expects($this->once())->method('create')
            ->with(
                142,
                101,
                'https://jira.example.com',
                'user@example.com',
                new ConcealedString('very_secret'),
                'jira project id',
                'jira issue type name',
                '10003',
                'Bugs',
                'bug',
                'inca-silver',
                'All bugs'
            )
            ->willReturn($uuid);

        $jira_runner = $this->createMock(JiraRunner::class);
        $jira_runner->expects($this->once())->method('queueJiraImportEvent')->with($uuid);

        $logger = new TestLogger();

        $scheduler = new AsyncJiraScheduler($pending_jira_import_dao, $jira_runner, new DBTransactionExecutorPassthrough());
        $scheduler->scheduleCreation(
            ProjectTestBuilder::aProject()->withId(142)->build(),
            UserTestBuilder::buildWithId(101),
            'https://jira.example.com',
            'user@example.com',
            new ConcealedString('very_secret'),
            'jira project id',
            'jira issue type name',
            '10003',
            'Bugs',
            'bug',
            'inca-silver',
            'All bugs'
        );
        self::assertFalse($logger->hasErrorRecords());
    }
}
