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

use Tuleap\Git\Hook\Asynchronous\CommitAnalysisOrder;
use Tuleap\Git\Stub\VerifyArtifactClosureIsAllowedStub;
use Tuleap\Git\Stub\VerifyIsDefaultBranchStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class PushCommitsAnalyzerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_COMMIT_SHA1  = '25c107ca25';
    private const SECOND_COMMIT_SHA1 = '154961e96f';

    private \PFUser $pusher;
    private \GitRepository $git_repository;
    private VerifyArtifactClosureIsAllowedStub $closure_verifier;
    private VerifyIsDefaultBranchStub $default_branch_verifier;

    protected function setUp(): void
    {
        $this->closure_verifier        = VerifyArtifactClosureIsAllowedStub::withAlwaysAllowed();
        $this->default_branch_verifier = VerifyIsDefaultBranchStub::withAlwaysDefaultBranch();

        $this->pusher         = UserTestBuilder::buildWithDefaults();
        $this->git_repository = $this->createStub(\GitRepository::class);
        $this->git_repository->method('getId')->willReturn(957);
    }

    /**
     * @return list<CommitAnalysisOrder>
     */
    private function analyzePushCommits(): array
    {
        $dispatcher = new PushCommitsAnalyzer($this->closure_verifier, $this->default_branch_verifier);
        $details    = new PushDetails(
            $this->git_repository,
            $this->pusher,
            'refs/heads/main',
            PushDetails::ACTION_UPDATE,
            PushDetails::OBJECT_TYPE_COMMIT,
            [self::FIRST_COMMIT_SHA1, self::SECOND_COMMIT_SHA1]
        );
        return $dispatcher->analyzePushCommits($details);
    }

    public function testItReturnsACommitAnalysisOrderForEachCommitOfThePushedReference(): void
    {
        $events = $this->analyzePushCommits();

        self::assertCount(2, $events);
        [$first_event, $second_event] = $events;

        self::assertSame(self::FIRST_COMMIT_SHA1, (string) $first_event->getCommitHash());
        self::assertSame($this->git_repository, $first_event->getRepository());
        self::assertSame($this->pusher, $first_event->getPusher());

        self::assertSame(self::SECOND_COMMIT_SHA1, (string) $second_event->getCommitHash());
        self::assertSame($this->git_repository, $second_event->getRepository());
        self::assertSame($this->pusher, $second_event->getPusher());
    }

    public function testItReturnsAnEmptyArrayIfArtifactClosureIsDisallowedOnTheGitRepository(): void
    {
        $this->closure_verifier = VerifyArtifactClosureIsAllowedStub::withNeverAllowed();

        self::assertCount(0, $this->analyzePushCommits());
    }

    public function testItReturnsAnEmptyArrayIfPushWasNotOnDefaultBranch(): void
    {
        $this->default_branch_verifier = VerifyIsDefaultBranchStub::withNeverDefaultBranch();

        self::assertCount(0, $this->analyzePushCommits());
    }
}
