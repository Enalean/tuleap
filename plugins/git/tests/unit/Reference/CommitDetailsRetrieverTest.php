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

use GitRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Head;
use Tuleap\Git\GitPHP\Tag;
use Tuleap\User\UserEmailCollection;

class CommitDetailsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsCommitDetailsFromDb(): void
    {
        $dao = Mockery::mock(
            CommitDetailsCacheDao::class,
            [
                'searchCommitDetails' => [
                    'title'           => 'Add foo to stuff',
                    'first_branch'    => 'dev-feature',
                    'first_tag'       => 'v1.2.0',
                    'author_email'    => 'jdoe@example.com',
                    'author_name'     => 'John Doe',
                    'committer_epoch' => 1234567890,
                ],
            ],
        );

        $john_doe     = Mockery::mock(\PFUser::class, ['getEmail' => 'jdoe@example.com']);
        $user_manager = Mockery::mock(
            \UserManager::class,
            [
                'getUserCollectionByEmails' => new UserEmailCollection($john_doe),
            ]
        );

        $retriever = new CommitDetailsRetriever($dao, $user_manager);

        $commit_details = $retriever->retrieveCommitDetails(
            Mockery::mock(GitRepository::class, ['getId' => 1]),
            Mockery::mock(Commit::class, ['GetHash' => '1a2b3c4d5e6f7g8h9i']),
        );

        self::assertEquals('Add foo to stuff', $commit_details->getTitle());
        self::assertEquals('dev-feature', $commit_details->getFirstBranch());
        self::assertEquals('v1.2.0', $commit_details->getFirstTag());
        self::assertEquals('jdoe@example.com', $commit_details->getAuthorEmail());
        self::assertEquals('John Doe', $commit_details->getAuthorName());
        self::assertEquals(1234567890, $commit_details->getCommitterEpoch());
    }

    public function testIfNotFoundInDbItReturnsCommitDetailsFromCommitAndCacheTheInformationInDb(): void
    {
        $dao = Mockery::mock(
            CommitDetailsCacheDao::class,
            [
                'searchCommitDetails' => [],
            ],
        );
        $dao->shouldReceive('saveCommitDetails')
            ->with(
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
            )
            ->once();

        $john_doe     = Mockery::mock(\PFUser::class, ['getEmail' => 'jdoe@example.com']);
        $user_manager = Mockery::mock(
            \UserManager::class,
            [
                'getUserCollectionByEmails' => new UserEmailCollection($john_doe),
            ]
        );

        $retriever = new CommitDetailsRetriever($dao, $user_manager);

        $commit_details = $retriever->retrieveCommitDetails(
            Mockery::mock(GitRepository::class, ['getId' => 1]),
            Mockery::mock(
                Commit::class,
                [
                    'GetHash'           => '1a2b3c4d5e6f7g8h9i',
                    'GetTitle'          => 'Add foo to stuff',
                    'GetAuthorEmail'    => 'jdoe@example.com',
                    'GetAuthorName'     => 'John Doe',
                    'GetAuthorEpoch'    => '1023456789',
                    'GetCommitterEmail' => 'neo@example.com',
                    'GetCommitterName'  => 'Thomas A. Anderson',
                    'GetCommitterEpoch' => '1234567890',
                    'GetHeads'          => [
                        Mockery::mock(Head::class)->shouldReceive(['GetName' => 'dev-feature'])->getMock(),
                    ],
                    'GetTags'           => [
                        Mockery::mock(Tag::class)->shouldReceive(['GetName' => 'v1.2.0'])->getMock(),
                    ],

                ]
            ),
        );

        self::assertEquals('Add foo to stuff', $commit_details->getTitle());
        self::assertEquals('dev-feature', $commit_details->getFirstBranch());
        self::assertEquals('v1.2.0', $commit_details->getFirstTag());
        self::assertEquals('jdoe@example.com', $commit_details->getAuthorEmail());
        self::assertEquals('John Doe', $commit_details->getAuthorName());
        self::assertEquals(1234567890, $commit_details->getCommitterEpoch());
    }

    public function testIfNotFoundInDbItReturnsNullIfCommitDoesNotHaveATitleBecauseInThatCaseWeAssumeThatTheCommitDoesNotExistInTheRepository(): void
    {
        $dao = Mockery::mock(
            CommitDetailsCacheDao::class,
            [
                'searchCommitDetails' => [],
            ],
        );
        $dao->shouldReceive('saveCommitDetails')
            ->never();

        $retriever = new CommitDetailsRetriever($dao, Mockery::mock(\UserManager::class));

        self::assertNull(
            $retriever->retrieveCommitDetails(
                Mockery::mock(GitRepository::class, ['getId' => 1]),
                Mockery::mock(
                    Commit::class,
                    [
                        'GetHash'  => '1a2b3c4d5e6f7g8h9i',
                        'GetTitle' => null,
                        'GetHeads' => null,
                        'GetTags'  => null,
                    ]
                ),
            )
        );
    }
}
