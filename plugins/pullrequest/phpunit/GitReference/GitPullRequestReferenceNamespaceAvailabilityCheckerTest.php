<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\GitReference;

use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\GitExec;

require_once __DIR__ . '/../bootstrap.php';

class GitPullRequestReferenceNamespaceAvailabilityCheckerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testNamespaceIsAvailableWhenNoGitReferenceAreFound()
    {
        $namespace_availability_checker = new GitPullRequestReferenceNamespaceAvailabilityChecker();

        $executor = \Mockery::mock(GitExec::class);
        $executor->shouldReceive('getReferencesFromPattern')->andReturns([]);

        $this->assertTrue($namespace_availability_checker->isAvailable($executor, 1));
    }

    public function testNamespaceIsNotAvailableWhenGitReferencesAreFound()
    {
        $namespace_availability_checker = new GitPullRequestReferenceNamespaceAvailabilityChecker();

        $executor = \Mockery::mock(GitExec::class);
        $executor->shouldReceive('getReferencesFromPattern')->andReturns(['refs/tlpr/1/head']);

        $this->assertFalse($namespace_availability_checker->isAvailable($executor, 1));
    }
}
