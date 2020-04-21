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

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\GitExec;

class GitPullRequestReferenceRemoverTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @dataProvider pullRequestReferencesProvider
     */
    public function testAllReferencesInPullRequestNamespaceAreRemoved(array $references)
    {
        $executor = \Mockery::mock(GitExec::class);
        $executor->shouldReceive('getReferencesFromPattern')->once()->andReturns($references);
        foreach ($references as $reference) {
            $executor->shouldReceive('removeReference')->with($reference);
        }

        $reference_remover = new GitPullRequestReferenceRemover();
        $reference_remover->removeAll($executor);
    }

    public function pullRequestReferencesProvider()
    {
        return [
            [[]],
            [[GitPullRequestReference::PR_NAMESPACE . '1/head', GitPullRequestReference::PR_NAMESPACE . '2/head']]
        ];
    }
}
