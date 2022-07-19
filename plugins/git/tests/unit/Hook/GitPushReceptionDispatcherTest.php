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

namespace Tuleap\Git\Hook;

use Psr\Log\NullLogger;
use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\Git\Hook\Asynchronous\CommitAnalysisProcessor;
use Tuleap\Git\Stub\BuildCommitAnalysisProcessorStub;
use Tuleap\Git\Stub\EventDispatcherStub;
use Tuleap\Git\Stub\RetrieveCommitMessageStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class GitPushReceptionDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_COMMIT_MESSAGE  = 'closes art #96';
    private const SECOND_COMMIT_MESSAGE = 'fixed bug #76';

    private EventDispatcherStub $event_dispatcher;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(162)->build();

        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();
    }

    private function dispatch(): void
    {
        $dispatcher = new GitPushReceptionDispatcher(
            BuildCommitAnalysisProcessorStub::withProcessor(
                new CommitAnalysisProcessor(
                    new NullLogger(),
                    RetrieveCommitMessageStub::withSuccessiveMessages(
                        self::FIRST_COMMIT_MESSAGE,
                        self::SECOND_COMMIT_MESSAGE
                    ),
                    $this->event_dispatcher,
                )
            )
        );
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getProject')->willReturn($this->project);
        $repository->method('getFullName')->willReturn('foamflower/newmarket');
        $details = new PushDetails(
            $repository,
            UserTestBuilder::buildWithDefaults(),
            'refs/heads/main',
            PushDetails::ACTION_UPDATE,
            PushDetails::OBJECT_TYPE_COMMIT,
            ['25c107ca25', '154961e96f']
        );
        $dispatcher->dispatchGitPushReception($details);
    }

    public function testItProcessesEachCommitOfThePushedReference(): void
    {
        /** @var list<PotentialReferencesReceived> $events */
        $events                 = [];
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            static function ($event) use (&$events) {
                $events[] = $event;
                return $event;
            }
        );

        $this->dispatch();

        self::assertCount(2, $events);
        [$first_event, $second_event] = $events;
        self::assertSame(self::FIRST_COMMIT_MESSAGE, $first_event->text_with_potential_references);
        self::assertSame($this->project, $first_event->project);
        self::assertSame(self::SECOND_COMMIT_MESSAGE, $second_event->text_with_potential_references);
        self::assertSame($this->project, $second_event->project);
    }
}
