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
use Tuleap\Git\Hook\PushDetails;
use Tuleap\Git\Tests\Stub\VerifyIsDefaultBranchStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class PushAnalyzerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_COMMIT_SHA1  = 'a53ff58a';
    private const SECOND_COMMIT_SHA1 = 'c7db9df4';

    private VerifyIsDefaultBranchStub $default_branch_verifier;
    /**
     * @var \GitRepository&Stub
     */
    private $repository;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->default_branch_verifier = VerifyIsDefaultBranchStub::withAlwaysDefaultBranch();
        $this->repository              = $this->createStub(\GitRepository::class);
        $this->user                    = UserTestBuilder::buildWithDefaults();
    }

    private function analyzePush(): ?DefaultBranchPushReceived
    {
        $analyzer = new PushAnalyzer($this->default_branch_verifier);
        return $analyzer->analyzePush(
            new PushDetails(
                $this->repository,
                $this->user,
                'refs/heads/main',
                PushDetails::ACTION_UPDATE,
                PushDetails::OBJECT_TYPE_COMMIT,
                [self::FIRST_COMMIT_SHA1, self::SECOND_COMMIT_SHA1]
            )
        );
    }

    public function testItReturnsDefaultBranchPushReceived(): void
    {
        $push = $this->analyzePush();

        self::assertSame($this->repository, $push->getRepository());
        self::assertSame($this->user, $push->getPusher());
        $string_hashes = array_map(
            static fn(CommitHash $commit_hash) => (string) $commit_hash,
            $push->getCommitHashes()
        );
        self::assertEqualsCanonicalizing([self::FIRST_COMMIT_SHA1, self::SECOND_COMMIT_SHA1], $string_hashes);
    }

    public function testItReturnsNullWhenPushWasNotOnDefaultBranch(): void
    {
        $this->default_branch_verifier = VerifyIsDefaultBranchStub::withNeverDefaultBranch();

        self::assertNull($this->analyzePush());
    }
}
