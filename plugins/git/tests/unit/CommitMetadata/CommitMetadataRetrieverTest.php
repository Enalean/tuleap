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

namespace Tuleap\Git\CommitMetadata;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Git\CommitStatus\CommitStatus;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\User\UserEmailCollection;

class CommitMetadataRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $status_retriever;
    /**
     * @var \Mockery\MockInterface
     */
    private $user_manager;

    protected function setUp(): void
    {
        $this->status_retriever = \Mockery::mock(CommitStatusRetriever::class);
        $this->user_manager     = \Mockery::mock(\UserManager::class);
    }

    public function testTuleapMetadataAssociatedWithCommitsAreRetrieved()
    {
        $metadata_retriever = new CommitMetadataRetriever($this->status_retriever, $this->user_manager);

        $commit_1 = \Mockery::mock(Commit::class);
        $commit_1->shouldReceive('GetHash')->andReturns('02e24c2314cb27f7b7c043345ca30c567c58e064');
        $commit_1->shouldReceive('getAuthorEmail')->andReturns('user1@example.com');
        $commit_1->shouldReceive('getCommitterEmail')->andReturns('user1@example.com');
        $commit_2 = \Mockery::mock(Commit::class);
        $commit_2->shouldReceive('GetHash')->andReturns('a37adf370551560c0b2ffd61a5737f8a836aac6d');
        $commit_2->shouldReceive('getAuthorEmail')->andReturns('user1@example.com');
        $commit_2->shouldReceive('getCommitterEmail')->andReturns('user1@example.com');

        $this->status_retriever->shouldReceive('getLastCommitStatuses')
            ->andReturns([\Mockery::mock(CommitStatus::class), \Mockery::mock(CommitStatus::class)]);

        $user = \Mockery::mock(\PFUser::class);
        $user_email_collection = \Mockery::mock(UserEmailCollection::class);
        $user_email_collection->shouldReceive('getUserByEmail')->andReturns($user);
        $this->user_manager->shouldReceive('getUserCollectionByEmails')->andReturns($user_email_collection);

        $repository = \Mockery::mock(\GitRepository::class);

        $all_metadata = $metadata_retriever->getMetadataByRepositoryAndCommits($repository, $commit_1, $commit_2);

        $this->assertCount(2, $all_metadata);
        foreach ($all_metadata as $commit_metadata) {
            $this->assertSame($user, $commit_metadata->getAuthor());
            $this->assertInstanceOf(CommitStatus::class, $commit_metadata->getCommitStatus());
        }
    }

    public function testEmptyAuthorEmailsAreIgnored()
    {
        $metadata_retriever = new CommitMetadataRetriever($this->status_retriever, $this->user_manager);

        $commit = \Mockery::mock(Commit::class);
        $commit->shouldReceive('GetHash')->andReturns('02e24c2314cb27f7b7c043345ca30c567c58e064');
        $commit->shouldReceive('getAuthorEmail')->andReturns('');
        $commit->shouldReceive('getCommitterEmail')->andReturns('');

        $user_email_collection = \Mockery::mock(UserEmailCollection::class);
        $user_email_collection->shouldReceive('getUserByEmail')->with('')->andReturnNull();
        $this->user_manager->shouldReceive('getUserCollectionByEmails')->with([])->andReturns($user_email_collection);

        $this->status_retriever->shouldReceive('getLastCommitStatuses')
            ->andReturns([\Mockery::mock(CommitStatus::class)]);

        $repository = \Mockery::mock(\GitRepository::class);

        $all_metadata = $metadata_retriever->getMetadataByRepositoryAndCommits($repository, $commit);
        $this->assertCount(1, $all_metadata);
        $this->assertNull($all_metadata[0]->getAuthor());
    }
}
