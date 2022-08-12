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

namespace Tuleap\Git\Hook\DefaultBranchPush;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Test\Builders\UserTestBuilder;

final class DefaultBranchPushReceivedTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_COMMIT_SHA1  = '14fdcf24';
    private const SECOND_COMMIT_SHA1 = 'e512f025';

    private \PFUser $user;
    /**
     * @var \GitRepository&Stub
     */
    private $repository;

    protected function setUp(): void
    {
        $this->user       = UserTestBuilder::buildWithDefaults();
        $this->repository = $this->createStub(\GitRepository::class);
        $this->repository->method('getId')->willReturn(43);
    }

    /**
     * @return list<CommitAnalysisOrder>
     */
    private function analyzeCommits(): array
    {
        $hashes = [
            CommitHash::fromString(self::FIRST_COMMIT_SHA1),
            CommitHash::fromString(self::SECOND_COMMIT_SHA1),
        ];

        $push = new DefaultBranchPushReceived($this->repository, $this->user, $hashes);

        return $push->analyzeCommits();
    }

    public function testItReturnsACommitAnalysisOrderForEachCommitHashOfThePush(): void
    {
        $orders = $this->analyzeCommits();

        self::assertCount(2, $orders);
        [$first_order, $second_order] = $orders;
        self::assertSame(self::FIRST_COMMIT_SHA1, (string) $first_order->getCommitHash());
        self::assertSame($this->user, $first_order->getPusher());
        self::assertSame($this->repository, $first_order->getRepository());

        self::assertSame(self::SECOND_COMMIT_SHA1, (string) $second_order->getCommitHash());
        self::assertSame($this->user, $second_order->getPusher());
        self::assertSame($this->repository, $second_order->getRepository());
    }
}
