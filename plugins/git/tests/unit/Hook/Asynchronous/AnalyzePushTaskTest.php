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

use Tuleap\Git\Hook\DefaultBranchPush\CommitHash;
use Tuleap\Git\Hook\DefaultBranchPush\DefaultBranchPushReceived;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AnalyzePushTaskTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_COMMIT_SHA1  = '98126434';
    private const SECOND_COMMIT_SHA1 = '93b34c74';
    private const PUSHING_USER_ID    = 183;
    private const GIT_REPOSITORY_ID  = 555;
    private \PFUser $pusher;
    /**
     * @var \GitRepository & \PHPUnit\Framework\MockObject\Stub
     */
    private $git_repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->pusher         = UserTestBuilder::aUser()->withId(self::PUSHING_USER_ID)->build();
        $this->git_repository = $this->createStub(\GitRepository::class);
        $this->git_repository->method('getId')->willReturn((string) self::GIT_REPOSITORY_ID);
        $this->git_repository->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
    }

    public function testItBuildsFromDefaultBranchPush(): void
    {
        $push = new DefaultBranchPushReceived(
            $this->git_repository,
            $this->pusher,
            [CommitHash::fromString(self::FIRST_COMMIT_SHA1), CommitHash::fromString(self::SECOND_COMMIT_SHA1)]
        );

        $task = AnalyzePushTask::fromDefaultBranchPush($push);

        self::assertSame([
            'commit_hashes'     => [self::FIRST_COMMIT_SHA1, self::SECOND_COMMIT_SHA1],
            'git_repository_id' => self::GIT_REPOSITORY_ID,
            'pushing_user_id'   => self::PUSHING_USER_ID,
        ], $task->getPayload());
        self::assertSame(AnalyzePushTask::TOPIC, $task->getTopic());
    }
}
