<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\Asynchronous;

use ColinODell\PsrTestLogger\TestLogger;
use Psr\Log\NullLogger;
use Tuleap\Git\Hook\DefaultBranchPush\DefaultBranchPushProcessor;
use Tuleap\Git\Stub\BuildDefaultBranchPushProcessorStub;
use Tuleap\Git\Stub\Hook\Asynchronous\RetrieveGitRepositoryStub;
use Tuleap\Git\Stub\RetrieveAuthorStub;
use Tuleap\Git\Stub\RetrieveCommitMessageStub;
use Tuleap\Git\Stub\VerifyArtifactClosureIsAllowedStub;
use Tuleap\NeverThrow\Fault;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\User\UserName;

final class AsynchronousEventHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PUSHING_USER_ID = 136;
    private string $topic;
    private TestLogger $logger;
    private RetrieveGitRepositoryStub $git_repository_retriever;
    private VerifyArtifactClosureIsAllowedStub $closure_verifier;
    private EventDispatcherStub $event_dispatcher;
    private RetrieveCommitMessageStub $commit_message_retriever;

    protected function setUp(): void
    {
        $project     = ProjectTestBuilder::aProject()->withId(198)->build();
        $this->topic = AnalyzePushTask::TOPIC;

        $git_repository = $this->createStub(\GitRepository::class);
        $git_repository->method('getId')->willReturn(54);
        $git_repository->method('getFullName')->willReturn('palpiform/bureau');
        $git_repository->method('getProject')->willReturn($project);

        $this->logger                   = new TestLogger();
        $this->git_repository_retriever = RetrieveGitRepositoryStub::withGitRepository($git_repository);
        $this->closure_verifier         = VerifyArtifactClosureIsAllowedStub::withAlwaysAllowed();
        $this->event_dispatcher         = EventDispatcherStub::withIdentityCallback();
        $this->commit_message_retriever = RetrieveCommitMessageStub::withSuccessiveMessages(
            'Closes story #973',
            'Implement story #147'
        );
    }

    private function handle(): void
    {
        $worker_event = new WorkerEvent(new NullLogger(), [
            'event_name' => $this->topic,
            'payload'    => [
                'git_repository_id' => 232,
                'commit_hashes'     => ['84d3a987', 'ec35bde4'],
                'pushing_user_id'   => self::PUSHING_USER_ID,
            ],
        ]);

        $user = UserTestBuilder::aUser()->withId(self::PUSHING_USER_ID)->build();

        $handler = new AsynchronousEventHandler(
            $this->logger,
            new DefaultBranchPushParser(RetrieveUserByIdStub::withUser($user), $this->git_repository_retriever),
            BuildDefaultBranchPushProcessorStub::withProcessor(
                new DefaultBranchPushProcessor(
                    $this->closure_verifier,
                    $this->commit_message_retriever,
                    RetrieveAuthorStub::buildWithUser(UserName::fromUser($user))
                )
            ),
            $this->event_dispatcher
        );
        $handler->handle($worker_event);
    }

    public function testItDispatchesAnEventToSearchForReferencesInEachCommitMessageOfTheWorkerEvent(): void
    {
        $this->handle();

        self::assertTrue($this->logger->hasDebugRecords());
        self::assertSame(1, $this->event_dispatcher->getCallCount());
    }

    public function testItIgnoresWorkerEventWithAnotherTopic(): void
    {
        $this->topic = 'bad topic';

        $this->handle();

        self::assertSame(0, $this->event_dispatcher->getCallCount());
        self::assertFalse($this->logger->hasDebugRecords());
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItLogsFaultsWhenParsingPush(): void
    {
        $error_message                  = 'Could not retrieve git repository';
        $this->git_repository_retriever = RetrieveGitRepositoryStub::withFault(Fault::fromMessage($error_message));

        $this->handle();

        self::assertSame(0, $this->event_dispatcher->getCallCount());
        self::assertTrue($this->logger->hasError($error_message));
    }

    public function testItDoesNotLogFaultWhenArtifactClosureIsDisabledInPushRepository(): void
    {
        $this->closure_verifier = VerifyArtifactClosureIsAllowedStub::withNeverAllowed();

        $this->handle();

        self::assertSame(0, $this->event_dispatcher->getCallCount());
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItLogsFaultsWhenProcessingPush(): void
    {
        $this->commit_message_retriever = RetrieveCommitMessageStub::withError();

        $this->handle();

        // It still dispatches the event when there are some faults there
        self::assertSame(1, $this->event_dispatcher->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
    }
}
