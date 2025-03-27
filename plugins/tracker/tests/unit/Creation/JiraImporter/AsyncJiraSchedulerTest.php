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
use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\TrackerCreationHasFailedException;

#[DisableReturnValueGenerationForTestDoubles]
final class AsyncJiraSchedulerTest extends TestCase
{
    public function testScheduleCreationStoreJiraInformationWithEncryptedToken(): void
    {
        $encryption_key = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));

        $key_factory = $this->createMock(KeyFactory::class);
        $key_factory->method('getEncryptionKey')->willReturn($encryption_key);

        $pending_jira_import_dao = $this->createMock(PendingJiraImportDao::class);
        $pending_jira_import_dao->expects($this->once())->method('create')
            ->with(
                142,
                101,
                'https://jira.example.com',
                'user@example.com',
                self::callback(
                    static fn(string $encrypted_jira_token) => SymmetricCrypto::decrypt($encrypted_jira_token, $encryption_key)->getString() === 'very_secret'
                ),
                'jira project id',
                'jira issue type name',
                '10003',
                'Bugs',
                'bug',
                'inca-silver',
                'All bugs'
            )
            ->willReturn(1001);

        $jira_runner = $this->createMock(JiraRunner::class);
        $jira_runner->expects($this->once())->method('queueJiraImportEvent')->with(1001);

        $logger = new TestLogger();

        $scheduler = new AsyncJiraScheduler($logger, $key_factory, $pending_jira_import_dao, $jira_runner);
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

    public function testItThrowsExceptionIfCreationFailed(): void
    {
        $encryption_key = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));

        $key_factory = $this->createMock(KeyFactory::class);
        $key_factory->method('getEncryptionKey')->willReturn($encryption_key);

        $pending_jira_import_dao = $this->createMock(PendingJiraImportDao::class);
        $pending_jira_import_dao->expects($this->once())->method('create')
            ->with(
                142,
                101,
                'https://jira.example.com',
                'user@example.com',
                self::callback(
                    static fn(string $encrypted_jira_token) => SymmetricCrypto::decrypt($encrypted_jira_token, $encryption_key)->getString() === 'very_secret'
                ),
                'jira project id',
                'jira issue type name',
                '10003',
                'Bugs',
                'bug',
                'inca-silver',
                'All bugs'
            )
            ->willReturn(0);

        $jira_runner = $this->createMock(JiraRunner::class);
        $jira_runner->expects(self::never())->method('queueJiraImportEvent');

        $logger = new TestLogger();

        $this->expectException(TrackerCreationHasFailedException::class);
        $scheduler = new AsyncJiraScheduler($logger, $key_factory, $pending_jira_import_dao, $jira_runner);
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
        self::assertTrue($logger->hasErrorThatContains('Unable to schedule the import of Jira: the pending jira import cannot be saved in DB.'));
    }

    public function testItThrowsExceptionIfTokenCannotBeEncrypted(): void
    {
        $key_factory = $this->createMock(KeyFactory::class);
        $key_factory->method('getEncryptionKey')->willThrowException(new CannotPerformIOOperationException('Cannot read encryption key'));

        $pending_jira_import_dao = $this->createMock(PendingJiraImportDao::class);
        $pending_jira_import_dao->expects(self::never())->method('create');

        $jira_runner = $this->createMock(JiraRunner::class);
        $jira_runner->expects(self::never())->method('queueJiraImportEvent');

        $logger = new TestLogger();

        $this->expectException(TrackerCreationHasFailedException::class);
        $scheduler = new AsyncJiraScheduler($logger, $key_factory, $pending_jira_import_dao, $jira_runner);
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
        self::assertTrue($logger->hasErrorThatContains('Unable to schedule the import of Jira: Cannot read encryption key'));
    }
}
