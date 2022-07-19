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

use Tuleap\Git\Hook\CommitHash;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class CommitAnalysisOrderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const COMMIT_SHA1 = 'bb7870508a';

    public function testItBuildsFromComponents(): void
    {
        $hash       = CommitHash::fromString(self::COMMIT_SHA1);
        $user       = UserTestBuilder::buildWithDefaults();
        $project    = ProjectTestBuilder::aProject()->build();
        $repository = $this->createStub(\GitRepository::class);

        $order = CommitAnalysisOrder::fromComponents($hash, $user, $repository, $project);

        self::assertSame($hash, $order->getCommitHash());
        self::assertSame($user, $order->getPusher());
        self::assertSame($project, $order->getProject());
        self::assertSame($repository, $order->getRepository());
    }
}
