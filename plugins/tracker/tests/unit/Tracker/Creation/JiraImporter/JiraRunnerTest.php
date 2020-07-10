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
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tracker;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraErrorImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraSuccessImportNotifier;
use UserManager;
use XML_ParseException;

class JiraRunnerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|QueueFactory
     */
    private $queue_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PendingJiraImportDao
     */
    private $dao;
    /**
     * @var JiraRunner
     */
    private $runner;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|KeyFactory
     */
    private $key_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FromJiraTrackerCreator
     */
    private $creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JiraSuccessImportNotifier
     */
    private $success_notifier;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JiraErrorImportNotifier
     */
    private $error_notifier;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var PFUser
     */
    private $anonymous_user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JiraUserOnTuleapCache
     */
    private $jira_user_on_tuleap_cache;

    protected function setUp(): void
    {
        $this->logger                    = Mockery::mock(LoggerInterface::class);
        $this->queue_factory             = Mockery::mock(QueueFactory::class);
        $this->dao                       = Mockery::mock(PendingJiraImportDao::class);
        $this->key_factory               = Mockery::mock(KeyFactory::class);
        $this->creator                   = Mockery::mock(FromJiraTrackerCreator::class);
        $this->success_notifier          = Mockery::mock(JiraSuccessImportNotifier::class);
        $this->error_notifier            = Mockery::mock(JiraErrorImportNotifier::class);
        $this->user_manager              = Mockery::mock(UserManager::class);
        $this->jira_user_on_tuleap_cache = Mockery::mock(JiraUserOnTuleapCache::class);

        $this->anonymous_user = new PFUser(['user_id' => 0, 'language_id' => 'en_US']);
        $this->user_manager->shouldReceive(['getUserAnonymous' => $this->anonymous_user]);

        $this->runner = new JiraRunner(
            $this->logger,
            $this->queue_factory,
            $this->key_factory,
            $this->creator,
            $this->dao,
            $this->success_notifier,
            $this->error_notifier,
            $this->user_manager,
            $this->jira_user_on_tuleap_cache
        );
    }

    public function testQueueJiraImportEvent(): void
    {
        $persistent_queue = Mockery::mock(PersistentQueue::class);
        $this->queue_factory
            ->shouldReceive('getPersistentQueue')
            ->with('app_user_events', 'redis')
            ->andReturn($persistent_queue);

        $persistent_queue
            ->shouldReceive('pushSinglePersistentMessage')
            ->with(
                'tuleap.tracker.creation.jira',
                [
                    'pending_jira_import_id' => 123,
                ]
            );

        $this->runner->queueJiraImportEvent(123);
    }

    public function testItLogsErrorWhenItCannotQueueTheEvent(): void
    {
        $persistent_queue = Mockery::mock(PersistentQueue::class);
        $this->queue_factory
            ->shouldReceive('getPersistentQueue')
            ->with('app_user_events', 'redis')
            ->andReturn($persistent_queue);

        $persistent_queue
            ->shouldReceive('pushSinglePersistentMessage')
            ->with(
                'tuleap.tracker.creation.jira',
                [
                    'pending_jira_import_id' => 123,
                ]
            )
            ->andThrow(\Exception::class);

        $this->logger
            ->shouldReceive('error')
            ->with('Unable to queue notification for Jira import #123.')
            ->once();

        $this->runner->queueJiraImportEvent(123);
    }

    public function testItCreatesTheProjectWithGreatSuccess(): void
    {
        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $project = Mockery::mock(\Project::class);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(['getName' => 'Whalter White', 'isAlive' => true]);

        $import          = Mockery::mock(PendingJiraImport::class);
        $encrypted_token = SymmetricCrypto::encrypt(
            new ConcealedString('secret'),
            $encryption_key
        );
        $import->shouldReceive(
            [
                'getId'                 => 123,
                'getUser'               => $user,
                'getProject'            => $project,
                'getTrackerName'        => 'Bugs',
                'getTrackerShortname'   => 'bug',
                'getTrackerDescription' => 'Imported issues from jira',
                'getTrackerColor'       => 'inca-silver',
                'getEncryptedJiraToken' => $encrypted_token,
                'getJiraUser'           => 'user@example.com',
                'getJiraServer'         => 'https://jira.example.com',
                'getJiraProjectId'      => 'Jira project',
                'getJiraIssueTypeName'  => 'Jira issue',
            ]
        );

        $this->user_manager
            ->shouldReceive('forceLogin')
            ->with('Whalter White')
            ->andReturn($user);

        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->once()
            ->andReturn($encryption_key);

        $tracker = Mockery::mock(Tracker::class);
        $this->creator
            ->shouldReceive('createFromJira')
            ->with(
                $project,
                'Bugs',
                'bug',
                'Imported issues from jira',
                'inca-silver',
                Mockery::on(
                    function (ConcealedString $token) {
                        return $token->getString() === 'secret';
                    }
                ),
                'user@example.com',
                'https://jira.example.com',
                'Jira project',
                'Jira issue',
                $user,
            )
            ->once()
            ->andReturn($tracker);

        $this->success_notifier
            ->shouldReceive('warnUserAboutSuccess')
            ->with($import, $tracker, $this->jira_user_on_tuleap_cache)
            ->once();

        $this->dao
            ->shouldReceive('deleteById')
            ->with(123)
            ->once();

        $this->user_manager
            ->shouldReceive('setCurrentUser')
            ->with($this->anonymous_user)
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItCannotProcessIfItCannotImpersonateTheUser(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(['getName' => 'Whalter White']);

        $import = Mockery::mock(PendingJiraImport::class);
        $import->shouldReceive(
            [
                'getId'   => 123,
                'getUser' => $user,
            ]
        );

        $this->user_manager
            ->shouldReceive('forceLogin')
            ->with('Whalter White')
            ->andReturn($this->anonymous_user);

        $this->logger
            ->shouldReceive('error')
            ->with('Unable to log in as the user who originated the event')
            ->once();

        $this->dao
            ->shouldReceive('deleteById')
            ->with(123)
            ->once();

        $this->user_manager
            ->shouldReceive('setCurrentUser')
            ->with($this->anonymous_user)
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItCannotProcessIfItCannotRetrieveTheEncryptionKey(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(['getName' => 'Whalter White', 'isAlive' => true]);

        $import = Mockery::mock(PendingJiraImport::class);
        $import->shouldReceive(
            [
                'getId'                 => 123,
                'getUser'               => $user,
                'getEncryptedJiraToken' => '0000000000101010'
            ]
        );

        $this->user_manager
            ->shouldReceive('forceLogin')
            ->with('Whalter White')
            ->andReturn($user);

        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->once()
            ->andThrow(CannotPerformIOOperationException::class);

        $this->logger
            ->shouldReceive('error')
            ->with('Unable to access to the token to do the import.')
            ->once();
        $this->error_notifier
            ->shouldReceive('warnUserAboutError')
            ->with($import, 'Unable to access to the token to do the import.')
            ->once();

        $this->dao
            ->shouldReceive('deleteById')
            ->with(123)
            ->once();

        $this->user_manager
            ->shouldReceive('setCurrentUser')
            ->with($this->anonymous_user)
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItCannotProcessIfItCannotDecryptTheToken(): void
    {
        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(['getName' => 'Whalter White', 'isAlive' => true]);

        $import          = Mockery::mock(PendingJiraImport::class);
        $encrypted_token = SymmetricCrypto::encrypt(
            new ConcealedString('secret'),
            new EncryptionKey(new ConcealedString(str_repeat('b', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)))
        );
        $import->shouldReceive(
            [
                'getId'                 => 123,
                'getUser'               => $user,
                'getEncryptedJiraToken' => $encrypted_token
            ]
        );

        $this->user_manager
            ->shouldReceive('forceLogin')
            ->with('Whalter White')
            ->andReturn($user);

        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->once()
            ->andReturn($encryption_key);

        $this->logger
            ->shouldReceive('error')
            ->with('The ciphertext cannot be decrypted')
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with('Unable to access to the token to do the import.')
            ->once();
        $this->error_notifier
            ->shouldReceive('warnUserAboutError')
            ->with($import, 'Unable to access to the token to do the import.')
            ->once();

        $this->dao
            ->shouldReceive('deleteById')
            ->with(123)
            ->once();

        $this->user_manager
            ->shouldReceive('setCurrentUser')
            ->with($this->anonymous_user)
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItWarnsTheUserInCaseOfJiraConnectionException(): void
    {
        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $project = Mockery::mock(\Project::class);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(['getName' => 'Whalter White', 'isAlive' => true]);

        $import          = Mockery::mock(PendingJiraImport::class);
        $encrypted_token = SymmetricCrypto::encrypt(
            new ConcealedString('secret'),
            $encryption_key
        );
        $import->shouldReceive(
            [
                'getId'                 => 123,
                'getUser'               => $user,
                'getProject'            => $project,
                'getTrackerName'        => 'Bugs',
                'getTrackerShortname'   => 'bug',
                'getTrackerDescription' => 'Imported issues from jira',
                'getTrackerColor'       => 'inca-silver',
                'getEncryptedJiraToken' => $encrypted_token,
                'getJiraUser'           => 'user@example.com',
                'getJiraServer'         => 'https://jira.example.com',
                'getJiraProjectId'      => 'Jira project',
                'getJiraIssueTypeName'  => 'Jira issue',
            ]
        );

        $this->user_manager
            ->shouldReceive('forceLogin')
            ->with('Whalter White')
            ->andReturn($user);

        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->once()
            ->andReturn($encryption_key);

        $tracker = Mockery::mock(Tracker::class);
        $this->creator
            ->shouldReceive('createFromJira')
            ->once()
            ->andThrow(JiraConnectionException::credentialsValuesAreInvalid());

        $this->logger
            ->shouldReceive('error')
            ->with('Can not connect to Jira server, please check your Jira credentials.')
            ->once();
        $this->error_notifier
            ->shouldReceive('warnUserAboutError')
            ->with($import, 'Can not connect to Jira server, please check your Jira credentials.')
            ->once();

        $this->dao
            ->shouldReceive('deleteById')
            ->with(123)
            ->once();

        $this->user_manager
            ->shouldReceive('setCurrentUser')
            ->with($this->anonymous_user)
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItWarnsTheUserInCaseOfXMLParseException(): void
    {
        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $project = Mockery::mock(\Project::class);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(['getName' => 'Whalter White', 'isAlive' => true]);

        $import          = Mockery::mock(PendingJiraImport::class);
        $encrypted_token = SymmetricCrypto::encrypt(
            new ConcealedString('secret'),
            $encryption_key
        );
        $import->shouldReceive(
            [
                'getId'                 => 123,
                'getUser'               => $user,
                'getProject'            => $project,
                'getTrackerName'        => 'Bugs',
                'getTrackerShortname'   => 'bug',
                'getTrackerDescription' => 'Imported issues from jira',
                'getTrackerColor'       => 'inca-silver',
                'getEncryptedJiraToken' => $encrypted_token,
                'getJiraUser'           => 'user@example.com',
                'getJiraServer'         => 'https://jira.example.com',
                'getJiraProjectId'      => 'Jira project',
                'getJiraIssueTypeName'  => 'Jira issue',
            ]
        );

        $this->user_manager
            ->shouldReceive('forceLogin')
            ->with('Whalter White')
            ->andReturn($user);

        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->once()
            ->andReturn($encryption_key);

        $tracker = Mockery::mock(Tracker::class);
        $this->creator
            ->shouldReceive('createFromJira')
            ->once()
            ->andThrow(new XML_ParseException('', [], []));

        $this->logger
            ->shouldReceive('error')
            ->with('Unable to parse the XML used to import from Jira.')
            ->once();
        $this->error_notifier
            ->shouldReceive('warnUserAboutError')
            ->with($import, 'Unable to parse the XML used to import from Jira.')
            ->once();

        $this->dao
            ->shouldReceive('deleteById')
            ->with(123)
            ->once();

        $this->user_manager
            ->shouldReceive('setCurrentUser')
            ->with($this->anonymous_user)
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItCanBeProcessedAsynchronously(): void
    {
        \ForgeConfig::set('sys_nb_backend_workers', 1);
        $this->queue_factory->shouldReceive(['getPersistentQueue' => Mockery::mock(PersistentQueue::class)]);
        $this->assertTrue($this->runner->canBeProcessedAsynchronously());
    }

    public function testItCannotBeProcessedAsynchronouslyIfNbOfBackendWorkersIsZero(): void
    {
        \ForgeConfig::set('sys_nb_backend_workers', 0);
        $this->queue_factory->shouldReceive(['getPersistentQueue' => Mockery::mock(PersistentQueue::class)]);
        $this->assertFalse($this->runner->canBeProcessedAsynchronously());
    }

    public function testItCannotBeProcessedAsynchronouslyIfNoopPersistentQueue(): void
    {
        \ForgeConfig::set('sys_nb_backend_workers', 1);
        $this->queue_factory->shouldReceive(['getPersistentQueue' => Mockery::mock(\Tuleap\Queue\Noop\PersistentQueue::class)]);
        $this->assertFalse($this->runner->canBeProcessedAsynchronously());
    }

    public function testItCannotBeProcessedAsynchronouslyIfNoQueueSystemAvailableException(): void
    {
        \ForgeConfig::set('sys_nb_backend_workers', 1);
        $this->queue_factory->shouldReceive('getPersistentQueue')->andThrow(NoQueueSystemAvailableException::class);
        $this->assertFalse($this->runner->canBeProcessedAsynchronously());
    }
}
