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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Tracker\Creation\TrackerCreationHasFailedException;

class AsyncJiraSchedulerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testScheduleCreationStoreJiraInformationWithEncryptedToken()
    {
        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $key_factory = Mockery::mock(KeyFactory::class);
        $key_factory->shouldReceive('getEncryptionKey')->andReturn($encryption_key);

        $pending_jira_import_dao = Mockery::mock(PendingJiraImportDao::class);
        $pending_jira_import_dao
            ->shouldReceive('create')
            ->with(
                42,
                101,
                'https://jira.example.com',
                'user@example.com',
                \Mockery::on(
                    static function (string $encrypted_jira_token) use ($encryption_key) {
                        return SymmetricCrypto::decrypt($encrypted_jira_token, $encryption_key)->getString() === 'very_secret';
                    }
                ),
                'jira project id',
                'jira issue type name',
                'Bugs',
                'bug',
                'inca-silver',
                'All bugs'
            )
            ->once()
            ->andReturn(1001);

        $jira_runner = Mockery::mock(JiraRunner::class);
        $jira_runner
            ->shouldReceive('queueJiraImportEvent')
            ->with(1001)
            ->once();
        $jira_runner
            ->shouldReceive('canBeProcessedAsynchronously')
            ->once()
            ->andReturn(true);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->never();

        $scheduler = new AsyncJiraScheduler($logger, $key_factory, $pending_jira_import_dao, $jira_runner);
        $scheduler->scheduleCreation(
            Mockery::mock(Project::class)->shouldReceive(['getID' => 42])->getMock(),
            Mockery::mock(\PFUser::class)->shouldReceive(['getId' => 101])->getMock(),
            'https://jira.example.com',
            'user@example.com',
            new ConcealedString('very_secret'),
            'jira project id',
            'jira issue type name',
            'Bugs',
            'bug',
            'inca-silver',
            'All bugs'
        );
    }

    public function testItThrowsExceptionIfCreationFailed()
    {
        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $key_factory = Mockery::mock(KeyFactory::class);
        $key_factory->shouldReceive('getEncryptionKey')->andReturn($encryption_key);

        $pending_jira_import_dao = Mockery::mock(PendingJiraImportDao::class);
        $pending_jira_import_dao
            ->shouldReceive('create')
            ->with(
                42,
                101,
                'https://jira.example.com',
                'user@example.com',
                \Mockery::on(
                    static function (string $encrypted_jira_token) use ($encryption_key) {
                        return SymmetricCrypto::decrypt($encrypted_jira_token, $encryption_key)->getString() === 'very_secret';
                    }
                ),
                'jira project id',
                'jira issue type name',
                'Bugs',
                'bug',
                'inca-silver',
                'All bugs'
            )
            ->once()
            ->andReturn(false);

        $jira_runner = Mockery::mock(JiraRunner::class);
        $jira_runner
            ->shouldReceive('queueJiraImportEvent')
            ->never();
        $jira_runner
            ->shouldReceive('canBeProcessedAsynchronously')
            ->once()
            ->andReturn(true);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->with('Unable to schedule the import of Jira: the pending jira import cannot be saved in DB.');

        $this->expectException(TrackerCreationHasFailedException::class);
        $scheduler = new AsyncJiraScheduler($logger, $key_factory, $pending_jira_import_dao, $jira_runner);
        $scheduler->scheduleCreation(
            Mockery::mock(Project::class)->shouldReceive(['getID' => 42])->getMock(),
            Mockery::mock(\PFUser::class)->shouldReceive(['getId' => 101])->getMock(),
            'https://jira.example.com',
            'user@example.com',
            new ConcealedString('very_secret'),
            'jira project id',
            'jira issue type name',
            'Bugs',
            'bug',
            'inca-silver',
            'All bugs'
        );
    }

    public function testItThrowsExceptionIfTokenCannotBeEncrypted()
    {
        $key_factory = Mockery::mock(KeyFactory::class);
        $key_factory
            ->shouldReceive('getEncryptionKey')
            ->andThrow(new CannotPerformIOOperationException('Cannot read encryption key'));

        $pending_jira_import_dao = Mockery::mock(PendingJiraImportDao::class);
        $pending_jira_import_dao
            ->shouldReceive('create')
            ->never();

        $jira_runner = Mockery::mock(JiraRunner::class);
        $jira_runner
            ->shouldReceive('queueJiraImportEvent')
            ->never();
        $jira_runner
            ->shouldReceive('canBeProcessedAsynchronously')
            ->once()
            ->andReturn(true);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->with('Unable to schedule the import of Jira: Cannot read encryption key');

        $this->expectException(TrackerCreationHasFailedException::class);
        $scheduler = new AsyncJiraScheduler($logger, $key_factory, $pending_jira_import_dao, $jira_runner);
        $scheduler->scheduleCreation(
            Mockery::mock(Project::class)->shouldReceive(['getID' => 42])->getMock(),
            Mockery::mock(\PFUser::class)->shouldReceive(['getId' => 101])->getMock(),
            'https://jira.example.com',
            'user@example.com',
            new ConcealedString('very_secret'),
            'jira project id',
            'jira issue type name',
            'Bugs',
            'bug',
            'inca-silver',
            'All bugs'
        );
    }

    public function testItThrowsExceptionIfEventCannotBeScheduled()
    {
        $key_factory = Mockery::mock(KeyFactory::class);

        $pending_jira_import_dao = Mockery::mock(PendingJiraImportDao::class);
        $pending_jira_import_dao
            ->shouldReceive('create')
            ->never();

        $jira_runner = Mockery::mock(JiraRunner::class);
        $jira_runner
            ->shouldReceive('queueJiraImportEvent')
            ->never();
        $jira_runner
            ->shouldReceive('canBeProcessedAsynchronously')
            ->once()
            ->andReturn(false);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->with('Unable to schedule the import of Jira: misconfiguration of the platform to queue the event.');

        $this->expectException(TrackerCreationHasFailedException::class);
        $scheduler = new AsyncJiraScheduler($logger, $key_factory, $pending_jira_import_dao, $jira_runner);
        $scheduler->scheduleCreation(
            Mockery::mock(Project::class)->shouldReceive(['getID' => 42])->getMock(),
            Mockery::mock(\PFUser::class)->shouldReceive(['getId' => 101])->getMock(),
            'https://jira.example.com',
            'user@example.com',
            new ConcealedString('very_secret'),
            'jira project id',
            'jira issue type name',
            'Bugs',
            'bug',
            'inca-silver',
            'All bugs'
        );
    }
}
