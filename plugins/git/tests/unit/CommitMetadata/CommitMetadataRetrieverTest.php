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

declare(strict_types=1);

namespace Tuleap\Git\CommitMetadata;

use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\CommitStatus\CommitStatus;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\UserEmailCollection;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommitMetadataRetrieverTest extends TestCase
{
    private readonly MockObject&CommitStatusRetriever $status_retriever;
    private readonly MockObject&UserManager $user_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->status_retriever = $this->createMock(CommitStatusRetriever::class);
        $this->user_manager     = $this->createMock(UserManager::class);
    }

    public function testTuleapMetadataAssociatedWithCommitsAreRetrieved()
    {
        $metadata_retriever = new CommitMetadataRetriever($this->status_retriever, $this->user_manager);

        $commit_1 = $this->createMock(Commit::class);
        $commit_1->method('GetHash')->willReturn('02e24c2314cb27f7b7c043345ca30c567c58e064');
        $commit_1->method('getAuthorEmail')->willReturn('user1@example.com');
        $commit_1->method('getCommitterEmail')->willReturn('user1@example.com');
        $commit_2 = $this->createMock(Commit::class);
        $commit_2->method('GetHash')->willReturn('a37adf370551560c0b2ffd61a5737f8a836aac6d');
        $commit_2->method('getAuthorEmail')->willReturn('user1@example.com');
        $commit_2->method('getCommitterEmail')->willReturn('user1@example.com');

        $this->status_retriever->method('getLastCommitStatuses')
            ->willReturn([$this->createMock(CommitStatus::class), $this->createMock(CommitStatus::class)]);

        $user                  = UserTestBuilder::buildWithDefaults();
        $user_email_collection = $this->createMock(UserEmailCollection::class);
        $user_email_collection->method('getUserByEmail')->willReturn($user);
        $this->user_manager->method('getUserCollectionByEmails')->willReturn($user_email_collection);

        $repository = $this->createMock(GitRepository::class);

        $all_metadata = $metadata_retriever->getMetadataByRepositoryAndCommits($repository, $commit_1, $commit_2);

        self::assertCount(2, $all_metadata);
        foreach ($all_metadata as $commit_metadata) {
            self::assertSame($user, $commit_metadata->getAuthor());
            self::assertInstanceOf(CommitStatus::class, $commit_metadata->getCommitStatus());
        }
    }

    public function testEmptyAuthorEmailsAreIgnored()
    {
        $metadata_retriever = new CommitMetadataRetriever($this->status_retriever, $this->user_manager);

        $commit = $this->createMock(Commit::class);
        $commit->method('GetHash')->willReturn('02e24c2314cb27f7b7c043345ca30c567c58e064');
        $commit->method('getAuthorEmail')->willReturn('');
        $commit->method('getCommitterEmail')->willReturn('');

        $user_email_collection = $this->createMock(UserEmailCollection::class);
        $user_email_collection->method('getUserByEmail')->with('')->willReturn(null);
        $this->user_manager->method('getUserCollectionByEmails')->with([])->willReturn($user_email_collection);

        $this->status_retriever->method('getLastCommitStatuses')
            ->willReturn([$this->createMock(CommitStatus::class)]);

        $repository = $this->createMock(GitRepository::class);

        $all_metadata = $metadata_retriever->getMetadataByRepositoryAndCommits($repository, $commit);
        self::assertCount(1, $all_metadata);
        self::assertNull($all_metadata[0]->getAuthor());
    }
}
