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


class GitlabReferenceExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsANullRepositoryNameAndANullSha1WhenTheStringDoesNotContainAPath(): void
    {
        [$repository_name, $sha1] = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId("a_string_with_no_path");
        self::assertEquals(null, $repository_name);
        self::assertEquals(null, $sha1);
    }

    public function testItReturnsTheRepositoryNameAndTheCommitSha1(): void
    {
        [$repository_name, $sha1] = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId(
            'john-snow/winter-is-coming/14a9b6c0c0c965977cf2af2199f93df82afcdea3'
        );
        self::assertEquals('john-snow/winter-is-coming', $repository_name);
        self::assertEquals('14a9b6c0c0c965977cf2af2199f93df82afcdea3', $sha1);
    }

    public function testItReturnsTheRepositoryNameAndTheMergeRequestId(): void
    {
        [$repository_name, $id] = GitlabReferenceExtractor::splitRepositoryNameAndReferencedItemId(
            'john-snow/winter-is-coming/25'
        );
        self::assertEquals('john-snow/winter-is-coming', $repository_name);
        self::assertEquals('25', $id);
    }
}
