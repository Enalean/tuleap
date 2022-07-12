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

use Psr\Log\Test\TestLogger;
use Tuleap\Git\Hook\Asynchronous\CommitAnalysisProcessor;
use Tuleap\Git\Stub\BuildCommitAnalysisProcessorStub;
use Tuleap\Git\Stub\EventDispatcherStub;
use Tuleap\Git\Stub\RetrieveCommitMessageStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class GitPushReceptionDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ARTIFACT_ID  = 96;
    private const SECOND_KEYWORD     = 'bug';
    private const SECOND_ARTIFACT_ID = 76;
    private const PROJECT_ID         = 162;
    private const FIRST_COMMIT_SHA1  = '25c107ca25';
    private const SECOND_COMMIT_SHA1 = '154961e96f';
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
    }

    private function dispatch(): void
    {
        $dispatcher = new GitPushReceptionDispatcher(
            BuildCommitAnalysisProcessorStub::withProcessor(
                new CommitAnalysisProcessor(
                    $this->logger,
                    RetrieveCommitMessageStub::withMessage(
                        'art #' . self::FIRST_ARTIFACT_ID . "\n " . self::SECOND_KEYWORD . '# ' . self::SECOND_ARTIFACT_ID
                    ),
                    EventDispatcherStub::withCallback(static fn($event) => $event),
                )
            )
        );
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getProject')->willReturn(
            ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()
        );
        $details = new PushDetails(
            $repository,
            UserTestBuilder::buildWithDefaults(),
            'refs/heads/main',
            PushDetails::ACTION_UPDATE,
            PushDetails::OBJECT_TYPE_COMMIT,
            [self::FIRST_COMMIT_SHA1, self::SECOND_COMMIT_SHA1]
        );
        $dispatcher->dispatchGitPushReception($details);
    }

    public function testItProcessesEachCommitOfThePushedReference(): void
    {
        $this->dispatch();
        self::assertTrue($this->logger->hasDebugThatContains('Analyzing commit with hash ' . self::FIRST_COMMIT_SHA1));
        self::assertTrue($this->logger->hasDebugThatContains('Analyzing commit with hash ' . self::SECOND_COMMIT_SHA1));
    }
}
