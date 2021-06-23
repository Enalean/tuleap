<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use Tuleap\Test\PHPUnit\TestCase;

class GitlabReferenceExtractorTest extends TestCase
{
    public function testItReturnsANullRepositoryNameAndANullSha1WhenTheStringDoesNotContainAPath(): void
    {
        $splitted_value = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId(
            "a_string_with_no_path"
        );
        self::assertEquals(null, $splitted_value->getRepositoryName());
        self::assertEquals(null, $splitted_value->getValue());
    }

    public function testItReturnsTheRepositoryNameAndTheCommitSha1(): void
    {
        $splitted_value = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId(
            'root/repo01/14a9b6c0c0c965977cf2af2199f93df82afcdea3'
        );
        self::assertEquals('root/repo01', $splitted_value->getRepositoryName());
        self::assertEquals('14a9b6c0c0c965977cf2af2199f93df82afcdea3', $splitted_value->getValue());
    }

    public function testItReturnsTheRepositoryNameAndTheMergeRequestId(): void
    {
        $splitted_value = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId(
            'root/repo01/25'
        );
        self::assertEquals('root/repo01', $splitted_value->getRepositoryName());
        self::assertEquals('25', $splitted_value->getValue());
    }

    public function testItReturnsTheRepositoryNameAndTheBranchName(): void
    {
        $splitted_value = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId(
            'root/repo01/dev/tuleap-25'
        );
        self::assertEquals('root/repo01', $splitted_value->getRepositoryName());
        self::assertEquals('dev/tuleap-25', $splitted_value->getValue());
    }
}
