<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\PullRequest\GitExec;

final class GitPullRequestReferenceRemoverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testAllReferencesInPullRequestNamespaceAreRemoved(): void
    {
        $reference_01 = GitPullRequestReference::PR_NAMESPACE . '1/head';
        $reference_02 = GitPullRequestReference::PR_NAMESPACE . '2/head';

        $executor = $this->createMock(GitExec::class);
        $executor->expects(self::once())->method('getReferencesFromPattern')->willReturn([
            $reference_01,
            $reference_02,
        ]);

        $executor->method('removeReference')->withConsecutive(
            [$reference_01],
            [$reference_02],
        );

        $reference_remover = new GitPullRequestReferenceRemover();
        $reference_remover->removeAll($executor);
    }

    public function testEmptyReferencesInPullRequestNamespaceAreNotRemoved(): void
    {
        $executor = $this->createMock(GitExec::class);
        $executor->expects(self::once())->method('getReferencesFromPattern')->willReturn([]);

        $executor->expects(self::never())->method('removeReference');

        $reference_remover = new GitPullRequestReferenceRemover();
        $reference_remover->removeAll($executor);
    }
}
