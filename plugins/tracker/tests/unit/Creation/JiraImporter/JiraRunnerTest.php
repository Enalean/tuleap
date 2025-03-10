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
use Psr\Log\LoggerInterface;
use Tracker;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraErrorImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraSuccessImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub;
use Tuleap\XML\ParseExceptionWithErrors;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JiraRunnerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

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

    private ClientWrapperBuilder $jira_client_builder;

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
        $this->jira_client_builder       = new ClientWrapperBuilder(
            fn () => new class extends JiraCloudClientStub{
            }
        );

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
            $this->jira_user_on_tuleap_cache,
            $this->jira_client_builder,
        );
    }

    public function testQueueJiraImportEvent(): void
    {
        $persistent_queue = Mockery::mock(PersistentQueue::class);
        $this->queue_factory
            ->shouldReceive('getPersistentQueue')
            ->with('app_user_events')
            ->andReturn($persistent_queue)
            ->atLeast()->once();

        $persistent_queue
            ->shouldReceive('pushSinglePersistentMessage')
            ->with(
                'tuleap.tracker.creation.jira',
                [
                    'pending_jira_import_id' => 123,
                ]
            )
            ->atLeast()->once();

        $this->runner->queueJiraImportEvent(123);
    }

    public function testItCreatesTheProjectWithGreatSuccess(): void
    {
        $this->logger->shouldReceive('debug');

        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $project = Mockery::mock(\Project::class);

        $user = UserTestBuilder::anActiveUser()->withUserName('Whalter White')->build();

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
                'getJiraIssueTypeId'    => '10003',
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
                    function (JiraCredentials $credentials) {
                        return $credentials->getJiraUrl() === 'https://jira.example.com' &&
                            $credentials->getJiraUsername() === 'user@example.com' &&
                            $credentials->getJiraToken()->getString() === 'secret';
                    }
                ),
                Mockery::type(JiraClient::class),
                'Jira project',
                '10003',
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
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItCannotProcessIfItCannotImpersonateTheUser(): void
    {
        $user = UserTestBuilder::aUser()->withUserName('Whalter White')->build();

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
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItCannotProcessIfItCannotRetrieveTheEncryptionKey(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('Whalter White')->build();

        $import = Mockery::mock(PendingJiraImport::class);
        $import->shouldReceive(
            [
                'getId'                 => 123,
                'getUser'               => $user,
                'getEncryptedJiraToken' => '0000000000101010',
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
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItCannotProcessIfItCannotDecryptTheToken(): void
    {
        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $user = UserTestBuilder::anActiveUser()->withUserName('Whalter White')->build();

        $import          = Mockery::mock(PendingJiraImport::class);
        $encrypted_token = SymmetricCrypto::encrypt(
            new ConcealedString('secret'),
            new EncryptionKey(new ConcealedString(str_repeat('b', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)))
        );
        $import->shouldReceive(
            [
                'getId'                 => 123,
                'getUser'               => $user,
                'getEncryptedJiraToken' => $encrypted_token,
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
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItWarnsTheUserInCaseOfJiraConnectionException(): void
    {
        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $project = ProjectTestBuilder::aProject()->build();

        $user = UserTestBuilder::anActiveUser()->withUserName('Whalter_White')->build();

        $encrypted_token = SymmetricCrypto::encrypt(
            new ConcealedString('secret'),
            $encryption_key
        );
        $import          = new PendingJiraImport(
            123,
            $project,
            $user,
            new \DateTimeImmutable(),
            'https://jira.example.com',
            'user@example.com',
            $encrypted_token,
            'JP',
            'Issues',
            '10003',
            'Bugs',
            'bug',
            'inca-silver',
            'Imported issues from jira',
        );

        $this->user_manager
            ->shouldReceive('forceLogin')
            ->with('Whalter_White')
            ->andReturn($user);

        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->once()
            ->andReturn($encryption_key);

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
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }

    public function testItWarnsTheUserInCaseOfXMLParseException(): void
    {
        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $project = ProjectTestBuilder::aProject()->build();

        $user = UserTestBuilder::anActiveUser()->withUserName('Whalter_White')->build();

        $encrypted_token = SymmetricCrypto::encrypt(
            new ConcealedString('secret'),
            $encryption_key
        );
        $import          = new PendingJiraImport(
            123,
            $project,
            $user,
            new \DateTimeImmutable(),
            'https://jira.example.com',
            'user@example.com',
            $encrypted_token,
            'JP',
            'Issues',
            '10003',
            'Bugs',
            'bug',
            'inca-silver',
            'Imported issues from jira',
        );

        $this->user_manager
            ->shouldReceive('forceLogin')
            ->with('Whalter_White')
            ->andReturn($user);

        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->once()
            ->andReturn($encryption_key);

        $this->creator
            ->shouldReceive('createFromJira')
            ->once()
            ->andThrow(new ParseExceptionWithErrors('', [], []));

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
            ->once();

        $this->runner->processAsyncJiraImport($import);
    }
}
