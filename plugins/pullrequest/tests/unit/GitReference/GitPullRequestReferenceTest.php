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

class GitPullRequestReferenceTest extends TestCase
{
    public function testInvalidStatusIsRejected()
    {
        $this->expectException(\DomainException::class);
        new GitPullRequestReference(1, 99999999999999999);
    }

    public function testBuildReferenceWithUpdatedId()
    {
        $reference         = new GitPullRequestReference(1, GitPullRequestReference::STATUS_OK);
        $updated_reference = GitPullRequestReference::buildReferenceWithUpdatedId(2, $reference);

        $this->assertEquals(2, $updated_reference->getGitReferenceId());
    }
}
