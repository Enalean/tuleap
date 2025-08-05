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
use DateTimeImmutable;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\SymmetricLegacy2025\EncryptionKey;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraErrorImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraSuccessImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraClientStub;
use Tuleap\XML\ParseExceptionWithErrors;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class JiraRunnerTest extends TestCase
{
    private TestLogger $logger;
    private QueueFactory&MockObject $queue_factory;
    private PendingJiraImportDao&MockObject $dao;
    private JiraRunner $runner;
    private KeyFactory&MockObject $key_factory;
    private FromJiraTrackerCreator&MockObject $creator;
    private JiraSuccessImportNotifier&MockObject $success_notifier;
    private JiraErrorImportNotifier&MockObject $error_notifier;
    private UserManager&MockObject $user_manager;
    private PFUser $anonymous_user;
    private JiraUserOnTuleapCache $jira_user_on_tuleap_cache;

    protected function setUp(): void
    {
        $this->logger                    = new TestLogger();
        $this->queue_factory             = $this->createMock(QueueFactory::class);
        $this->dao                       = $this->createMock(PendingJiraImportDao::class);
        $this->key_factory               = $this->createMock(KeyFactory::class);
        $this->creator                   = $this->createMock(FromJiraTrackerCreator::class);
        $this->success_notifier          = $this->createMock(JiraSuccessImportNotifier::class);
        $this->error_notifier            = $this->createMock(JiraErrorImportNotifier::class);
        $this->user_manager              = $this->createMock(UserManager::class);
        $this->jira_user_on_tuleap_cache = new JiraUserOnTuleapCache(new JiraTuleapUsersMapping(), UserTestBuilder::buildWithDefaults());

        $this->anonymous_user = UserTestBuilder::anAnonymousUser()->build();
        $this->user_manager->method('getUserAnonymous')->willReturn($this->anonymous_user);

        $this->runner = new JiraRunner(
            $this->logger,
            $this->queue_factory,
            $this->key_factory,
            $this->creator,
            $this->dao,
            $this->success_notifier,
            $this->error_notifier,
            $this->user_manager,
            $this->jira_user_on_tuleap_cache,
            new ClientWrapperBuilder(static fn() => JiraClientStub::aJiraClient()),
        );
    }

    public function testQueueJiraImportEvent(): void
    {
        $persistent_queue = $this->createMock(PersistentQueue::class);
        $this->queue_factory->expects($this->atLeastOnce())->method('getPersistentQueue')
            ->with('app_user_events')->willReturn($persistent_queue);

        $persistent_queue->expects($this->atLeastOnce())->method('pushSinglePersistentMessage')
            ->with(
                'tuleap.tracker.creation.jira',
                [
                    'pending_jira_import_id' => 123,
                ]
            );

        $this->runner->queueJiraImportEvent(123);
    }

    public function testItCreatesTheProjectWithGreatSuccess(): void
    {
        $encryption_key = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
        $project        = ProjectTestBuilder::aProject()->build();
        $user           = UserTestBuilder::anActiveUser()->withUserName('Whalter White')->build();

        $import = new PendingJiraImport(
            123,
            $project,
            $user,
            new DateTimeImmutable(),
            'https://jira.example.com',
            'user@example.com',
            SymmetricCrypto::encrypt(new ConcealedString('secret'), $encryption_key),
            'Jira project',
            'Issues',
            '10003',
            'Bugs',
            'bug',
            'inca-silver',
            'Imported issues from jira',
        );

        $this->user_manager->method('forceLogin')->with('Whalter White')->willReturn($user);
        $this->key_factory->expects($this->once())->method('getEncryptionKey')->willReturn($encryption_key);

        $tracker = TrackerTestBuilder::aTracker()->build();
        $this->creator->expects($this->once())->method('createFromJira')
            ->with(
                $project,
                'Bugs',
                'bug',
                'Imported issues from jira',
                'inca-silver',
                self::callback(
                    static fn(JiraCredentials $credentials) => $credentials->getJiraUrl() === 'https://jira.example.com' &&
                                                               $credentials->getJiraUsername() === 'user@example.com' &&
                                                               $credentials->getJiraToken()->getString() === 'secret'
                ),
                self::isInstanceOf(JiraClient::class),
                'Jira project',
                '10003',
                $user,
            )
            ->willReturn($tracker);

        $this->success_notifier->expects($this->once())->method('warnUserAboutSuccess')
            ->with($import, $tracker, $this->jira_user_on_tuleap_cache);

        $this->dao->expects($this->once())->method('deleteById')->with(123);

        $this->user_manager->expects($this->once())->method('setCurrentUser');

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItCannotProcessIfItCannotImpersonateTheUser(): void
    {
        $user = UserTestBuilder::aUser()->withUserName('Whalter White')->build();

        $import = $this->createMock(PendingJiraImport::class);
        $import->method('getId')->willReturn(123);
        $import->method('getUser')->willReturn($user);

        $this->user_manager->method('forceLogin')->with('Whalter White')->willReturn($this->anonymous_user);

        $this->dao->expects($this->once())->method('deleteById')->with(123);

        $this->user_manager->expects($this->once())->method('setCurrentUser');

        $this->runner->processAsyncJiraImport($import);
        self::assertTrue($this->logger->hasErrorThatContains('Unable to log in as the user who originated the event'));
    }

    public function testItCannotProcessIfItCannotRetrieveTheEncryptionKey(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('Whalter White')->build();

        $import = $this->createMock(PendingJiraImport::class);
        $import->method('getId')->willReturn(123);
        $import->method('getUser')->willReturn($user);
        $import->method('getEncryptedJiraToken')->willReturn('0000000000101010');

        $this->user_manager->method('forceLogin')->with('Whalter White')->willReturn($user);

        $this->key_factory->expects($this->once())->method('getEncryptionKey')
            ->willThrowException(new CannotPerformIOOperationException(''));

        $this->error_notifier->expects($this->once())->method('warnUserAboutError')
            ->with($import, 'Unable to access to the token to do the import.');

        $this->dao->expects($this->once())->method('deleteById')->with(123);

        $this->user_manager->expects($this->once())->method('setCurrentUser');

        $this->runner->processAsyncJiraImport($import);
        self::assertTrue($this->logger->hasErrorThatContains('Unable to access to the token to do the import.'));
    }

    public function testItCannotProcessIfItCannotDecryptTheToken(): void
    {
        $encryption_key = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));

        $user = UserTestBuilder::anActiveUser()->withUserName('Whalter White')->build();

        $import          = $this->createMock(PendingJiraImport::class);
        $encrypted_token = SymmetricCrypto::encrypt(
            new ConcealedString('secret'),
            new EncryptionKey(new ConcealedString(str_repeat('b', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)))
        );
        $import->method('getId')->willReturn(123);
        $import->method('getUser')->willReturn($user);
        $import->method('getEncryptedJiraToken')->willReturn($encrypted_token);

        $this->user_manager->method('forceLogin')->with('Whalter White')->willReturn($user);

        $this->key_factory->expects($this->once())->method('getEncryptionKey')->willReturn($encryption_key);

        $this->error_notifier->expects($this->once())->method('warnUserAboutError')
            ->with($import, 'Unable to access to the token to do the import.');

        $this->dao->expects($this->once())->method('deleteById')->with(123);

        $this->user_manager->expects($this->once())->method('setCurrentUser');

        $this->runner->processAsyncJiraImport($import);
        self::assertTrue($this->logger->hasErrorThatContains('The ciphertext cannot be decrypted'));
        self::assertTrue($this->logger->hasErrorThatContains('Unable to access to the token to do the import.'));
    }

    public function testItWarnsTheUserInCaseOfJiraConnectionException(): void
    {
        $encryption_key = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
        $user           = UserTestBuilder::anActiveUser()->withUserName('Whalter_White')->build();
        $import         = new PendingJiraImport(
            123,
            ProjectTestBuilder::aProject()->build(),
            $user,
            new DateTimeImmutable(),
            'https://jira.example.com',
            'user@example.com',
            SymmetricCrypto::encrypt(new ConcealedString('secret'), $encryption_key),
            'JP',
            'Issues',
            '10003',
            'Bugs',
            'bug',
            'inca-silver',
            'Imported issues from jira',
        );

        $this->user_manager->method('forceLogin')->with('Whalter_White')->willReturn($user);

        $this->key_factory->expects($this->once())->method('getEncryptionKey')->willReturn($encryption_key);

        $this->creator->expects($this->once())->method('createFromJira')
            ->willThrowException(JiraConnectionException::credentialsValuesAreInvalid());

        $this->error_notifier->expects($this->once())->method('warnUserAboutError')
            ->with($import, 'Can not connect to Jira server, please check your Jira credentials.');

        $this->dao->expects($this->once())->method('deleteById')->with(123);

        $this->user_manager->expects($this->once())->method('setCurrentUser');

        $this->runner->processAsyncJiraImport($import);
        self::assertTrue($this->logger->hasErrorThatContains('Can not connect to Jira server, please check your Jira credentials.'));
    }

    public function testItWarnsTheUserInCaseOfXMLParseException(): void
    {
        $encryption_key = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
        $user           = UserTestBuilder::anActiveUser()->withUserName('Whalter_White')->build();
        $import         = new PendingJiraImport(
            123,
            ProjectTestBuilder::aProject()->build(),
            $user,
            new DateTimeImmutable(),
            'https://jira.example.com',
            'user@example.com',
            SymmetricCrypto::encrypt(new ConcealedString('secret'), $encryption_key),
            'JP',
            'Issues',
            '10003',
            'Bugs',
            'bug',
            'inca-silver',
            'Imported issues from jira',
        );

        $this->user_manager->method('forceLogin')->with('Whalter_White')->willReturn($user);

        $this->key_factory->expects($this->once())->method('getEncryptionKey')->willReturn($encryption_key);

        $this->creator->expects($this->once())->method('createFromJira')
            ->willThrowException(new ParseExceptionWithErrors('', [], []));

        $this->error_notifier->expects($this->once())->method('warnUserAboutError')
            ->with($import, 'Unable to parse the XML used to import from Jira.');

        $this->dao->expects($this->once())->method('deleteById')->with(123);

        $this->user_manager->expects($this->once())->method('setCurrentUser');

        $this->runner->processAsyncJiraImport($import);
        self::assertTrue($this->logger->hasErrorThatContains('Unable to parse the XML used to import from Jira.'));
    }
}
