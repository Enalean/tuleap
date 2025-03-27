<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Reference;

use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Head;
use Tuleap\Git\GitPHP\Tag;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommitDetailsRetrieverTest extends TestCase
{
    public function testItReturnsCommitDetailsFromDb(): void
    {
        $dao = $this->createMock(CommitDetailsCacheDao::class);
        $dao->method('searchCommitDetails')->willReturn([
            'title'           => 'Add foo to stuff',
            'first_branch'    => 'dev-feature',
            'first_tag'       => 'v1.2.0',
            'author_email'    => 'jdoe@example.com',
            'author_name'     => 'John Doe',
            'committer_epoch' => 1234567890,
        ]);

        $retriever = new CommitDetailsRetriever($dao);

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHash')->willReturn('1a2b3c4d5e6f7g8h9i');
        $commit_details = $retriever->retrieveCommitDetails(GitRepositoryTestBuilder::aProjectRepository()->withId(1)->build(), $commit);

        self::assertEquals('Add foo to stuff', $commit_details->getTitle());
        self::assertEquals('dev-feature', $commit_details->getFirstBranch());
        self::assertEquals('v1.2.0', $commit_details->getFirstTag());
        self::assertEquals('jdoe@example.com', $commit_details->getAuthorEmail());
        self::assertEquals('John Doe', $commit_details->getAuthorName());
        self::assertEquals(1234567890, $commit_details->getCommitterEpoch());
    }

    public function testIfNotFoundInDbItReturnsCommitDetailsFromCommitAndCacheTheInformationInDb(): void
    {
        $dao = $this->createMock(CommitDetailsCacheDao::class);
        $dao->method('searchCommitDetails')->willReturn([]);
        $dao->expects($this->once())->method('saveCommitDetails')->with(
            1,
            '1a2b3c4d5e6f7g8h9i',
            'Add foo to stuff',
            'jdoe@example.com',
            'John Doe',
            1023456789,
            'neo@example.com',
            'Thomas A. Anderson',
            1234567890,
            'dev-feature',
            'v1.2.0',
        );

        $retriever = new CommitDetailsRetriever($dao);

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHash')->willReturn('1a2b3c4d5e6f7g8h9i');
        $commit->method('GetTitle')->willReturn('Add foo to stuff');
        $commit->method('GetAuthorEmail')->willReturn('jdoe@example.com');
        $commit->method('GetAuthorName')->willReturn('John Doe');
        $commit->method('GetAuthorEpoch')->willReturn('1023456789');
        $commit->method('GetCommitterEmail')->willReturn('neo@example.com');
        $commit->method('GetCommitterName')->willReturn('Thomas A. Anderson');
        $commit->method('GetCommitterEpoch')->willReturn('1234567890');
        $head = $this->createMock(Head::class);
        $head->method('GetName')->willReturn('dev-feature');
        $commit->method('GetHeads')->willReturn([$head]);
        $tag = $this->createMock(Tag::class);
        $tag->method('GetName')->willReturn('v1.2.0');
        $commit->method('GetTags')->willReturn([$tag]);
        $commit_details = $retriever->retrieveCommitDetails(GitRepositoryTestBuilder::aProjectRepository()->withId(1)->build(), $commit);

        self::assertEquals('Add foo to stuff', $commit_details->getTitle());
        self::assertEquals('dev-feature', $commit_details->getFirstBranch());
        self::assertEquals('v1.2.0', $commit_details->getFirstTag());
        self::assertEquals('jdoe@example.com', $commit_details->getAuthorEmail());
        self::assertEquals('John Doe', $commit_details->getAuthorName());
        self::assertEquals(1234567890, $commit_details->getCommitterEpoch());
    }

    public function testIfNotFoundInDbItReturnsNullIfCommitDoesNotHaveATitleBecauseInThatCaseWeAssumeThatTheCommitDoesNotExistInTheRepository(): void
    {
        $dao = $this->createMock(CommitDetailsCacheDao::class);
        $dao->method('searchCommitDetails')->willReturn([]);
        $dao->expects(self::never())->method('saveCommitDetails');

        $retriever = new CommitDetailsRetriever($dao);

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHash')->willReturn('1a2b3c4d5e6f7g8h9i');
        $commit->method('GetTitle')->willReturn(null);
        $commit->method('GetHeads')->willReturn(null);
        $commit->method('GetTags')->willReturn(null);
        self::assertNull($retriever->retrieveCommitDetails(GitRepositoryTestBuilder::aProjectRepository()->withId(1)->build(), $commit));
    }
}
