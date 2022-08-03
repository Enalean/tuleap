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

use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\Git\Stub\BuildCommitAnalysisProcessorStub;
use Tuleap\Git\Stub\EventDispatcherStub;
use Tuleap\Git\Stub\RetrieveCommitMessageStub;
use Tuleap\Git\Stub\RetrieveGitRepositoryStub;
use Tuleap\NeverThrow\Fault;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class AsynchronousEventHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const COMMIT_MESSAGE      = 'Closes story #973';
    private const GIT_REPOSITORY_NAME = 'palpiform/bureau';
    private const PUSHING_USER_ID     = 136;
    private const COMMIT_SHA1         = '84d3a987';
    private string $topic;
    private TestLogger $logger;
    private \PFUser $user;
    private \Project $project;
    private RetrieveGitRepositoryStub $git_repository_retriever;
    private EventDispatcherStub $event_dispatcher;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::aUser()->withId(self::PUSHING_USER_ID)->build();
        $this->project = ProjectTestBuilder::aProject()->withId(198)->build();
        $this->topic   = AnalyzeCommitTask::TOPIC;

        $git_repository = $this->createStub(\GitRepository::class);
        $git_repository->method('getFullName')->willReturn(self::GIT_REPOSITORY_NAME);
        $git_repository->method('getProject')->willReturn($this->project);

        $this->logger                   = new TestLogger();
        $this->git_repository_retriever = RetrieveGitRepositoryStub::withGitRepository($git_repository);
        $this->event_dispatcher         = EventDispatcherStub::withIdentityCallback();
    }

    private function handle(): void
    {
        $worker_event = new WorkerEvent(new NullLogger(), [
            'event_name' => $this->topic,
            'payload'    => [
                'git_repository_id' => 232,
                'commit_sha1'       => self::COMMIT_SHA1,
                'pushing_user_id'   => self::PUSHING_USER_ID,
            ],
        ]);

        $handler = new AsynchronousEventHandler(
            $this->logger,
            new CommitAnalysisOrderParser(RetrieveUserByIdStub::withUser($this->user), $this->git_repository_retriever),
            BuildCommitAnalysisProcessorStub::withProcessor(
                new CommitAnalysisProcessor(RetrieveCommitMessageStub::withMessage(self::COMMIT_MESSAGE))
            ),
            $this->event_dispatcher
        );
        $handler->handle($worker_event);
    }

    public function testItDispatchesAnEventToSearchReferencesInTheCommitMessageOfTheWorkerEvent(): void
    {
        /** @var ?PotentialReferencesReceived $event */
        $event                  = null;
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            static function (PotentialReferencesReceived $received) use (&$event) {
                $event = $received;
                return $received;
            }
        );

        $this->handle();

        self::assertNotNull($event);
        self::assertSame($this->project, $event->project);
        self::assertSame($this->user, $event->user);
        self::assertSame(self::COMMIT_MESSAGE, $event->text_with_potential_references);
        self::assertStringContainsString(self::COMMIT_SHA1, $event->back_reference->getStringReference());
        self::assertStringContainsString(self::GIT_REPOSITORY_NAME, $event->back_reference->getStringReference());
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItIgnoresWorkerEventWithAnotherTopic(): void
    {
        $this->topic = 'bad topic';

        $this->handle();

        self::assertSame(0, $this->event_dispatcher->getCallCount());
        self::assertFalse($this->logger->hasDebugRecords());
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItLogsFaults(): void
    {
        $error_message                  = 'Could not retrieve git repository';
        $this->git_repository_retriever = RetrieveGitRepositoryStub::withFault(Fault::fromMessage($error_message));

        $this->handle();

        self::assertSame(0, $this->event_dispatcher->getCallCount());
        self::assertTrue($this->logger->hasError($error_message));
    }
}
