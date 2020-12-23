<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Git\CommitMetadata\CommitMetadata;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\CommitStatus\CommitStatusUnknown;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Head;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class CrossReferenceGitEnhancerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CommitMetadataRetriever
     */
    private $commit_metadata_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserHelper
     */
    private $user_helper;
    /**
     * @var CrossReferenceGitEnhancer
     */
    private $enhancer;
    /**
     * @var \GitRepository|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $repository;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Commit
     */
    private $commit;

    protected function setUp(): void
    {
        $this->commit_metadata_retriever = Mockery::mock(CommitMetadataRetriever::class);
        $this->user_helper               = Mockery::mock(\UserHelper::class);

        $this->enhancer = new CrossReferenceGitEnhancer(
            $this->commit_metadata_retriever,
            $this->user_helper,
            new TlpRelativeDatePresenterBuilder(),
        );

        $this->repository = Mockery::mock(\GitRepository::class);

        $this->user = Mockery::mock(\PFUser::class)
            ->shouldReceive(
                [
                    'getPreference' => 'relative_first-absolute_tooltip',
                    'getLocale'     => 'en_US'
                ]
            )
            ->getMock();

        $this->commit = Mockery::mock(Commit::class)
            ->shouldReceive(
                [
                    'GetTitle'       => 'Add foo to stuff',
                    'GetHash'        => '1a2b3c4d5e6f7g8h9i',
                    'GetAuthorEpoch' => 1234567890,
                ]
            )
            ->getMock();

        $GLOBALS['Language']
            ->shouldReceive('getText')
            ->with('system', 'datefmt')
            ->andReturn('d/m/Y H:i');
    }

    public function testItDisplaysCommitTitleAsXRefTitle(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $this->commit_metadata_retriever
            ->shouldReceive('getMetadataByRepositoryAndCommits')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetHeads')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetTags')
            ->andReturn([]);

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            $this->commit,
            $this->repository,
            $this->user
        );

        self::assertEquals(
            'Add foo to stuff',
            $new_ref->title,
        );
    }

    public function testItDisplaysOnlyCommitSha1AsAdditionalBadgeWhenThereIsNoBranchNorTag(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $this->commit_metadata_retriever
            ->shouldReceive('getMetadataByRepositoryAndCommits')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetHeads')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetTags')
            ->andReturn([]);

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            $this->commit,
            $this->repository,
            $this->user
        );

        self::assertCount(1, $new_ref->additional_badges);
        self::assertEquals('1a2b3c4d5e', $new_ref->additional_badges[0]->label);
        self::assertFalse($new_ref->additional_badges[0]->is_plain);
        self::assertFalse($new_ref->additional_badges[0]->is_primary);
    }

    public function testItDisplaysCommitSha1AndFirstBranchAsAdditionalBadges(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $this->commit_metadata_retriever
            ->shouldReceive('getMetadataByRepositoryAndCommits')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetHeads')
            ->andReturn([
                Mockery::mock(Head::class)->shouldReceive(['GetName' => 'dev-feature'])->getMock(),
            ]);

        $this->commit
            ->shouldReceive('GetTags')
            ->andReturn([
                Mockery::mock(Head::class)->shouldReceive(['GetName' => 'v1.2.0'])->getMock(),
            ]);

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            $this->commit,
            $this->repository,
            $this->user
        );

        self::assertCount(2, $new_ref->additional_badges);

        self::assertEquals('dev-feature', $new_ref->additional_badges[0]->label);
        self::assertFalse($new_ref->additional_badges[0]->is_plain);
        self::assertTrue($new_ref->additional_badges[0]->is_primary);

        self::assertEquals('1a2b3c4d5e', $new_ref->additional_badges[1]->label);
        self::assertFalse($new_ref->additional_badges[1]->is_plain);
        self::assertFalse($new_ref->additional_badges[1]->is_primary);
    }

    public function testItDisplaysCommitSha1AndFirstTagAsAdditionalBadgesWhenThereIsNoBranch(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $this->commit_metadata_retriever
            ->shouldReceive('getMetadataByRepositoryAndCommits')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetHeads')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetTags')
            ->andReturn([
                Mockery::mock(Head::class)->shouldReceive(['GetName' => 'v1.2.0'])->getMock(),
            ]);

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            $this->commit,
            $this->repository,
            $this->user
        );

        self::assertCount(2, $new_ref->additional_badges);

        self::assertEquals('v1.2.0', $new_ref->additional_badges[0]->label);
        self::assertTrue($new_ref->additional_badges[0]->is_plain);
        self::assertTrue($new_ref->additional_badges[0]->is_primary);

        self::assertEquals('1a2b3c4d5e', $new_ref->additional_badges[1]->label);
        self::assertFalse($new_ref->additional_badges[1]->is_plain);
        self::assertFalse($new_ref->additional_badges[1]->is_primary);
    }

    public function testItDoesNotAddCreationMetadataIfTheyCannotBeFound(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $this->commit_metadata_retriever
            ->shouldReceive('getMetadataByRepositoryAndCommits')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetHeads')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetTags')
            ->andReturn([]);

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            $this->commit,
            $this->repository,
            $this->user
        );

        self::assertNull($new_ref->creation_metadata);
    }

    public function testItAddCreationDateAsMetadata(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $author = Mockery::mock(\PFUser::class)
            ->shouldReceive(
                [
                    'hasAvatar'    => true,
                    'getAvatarUrl' => '/path/to/avatar',
                ]
            )
            ->getMock();

        $this->user_helper
            ->shouldReceive('getDisplayNameFromUser')
            ->with($author)
            ->andReturn('John Doe');

        $this->commit_metadata_retriever
            ->shouldReceive('getMetadataByRepositoryAndCommits')
            ->andReturn(
                [
                    new CommitMetadata(new CommitStatusUnknown(), $author, $author),
                ]
            );

        $this->commit
            ->shouldReceive('GetHeads')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetTags')
            ->andReturn([]);

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            $this->commit,
            $this->repository,
            $this->user
        );

        self::assertEquals('2009-02-14T00:31:30+01:00', $new_ref->creation_metadata->created_on->date);
        self::assertEquals('14/02/2009 00:31', $new_ref->creation_metadata->created_on->absolute_date);
        self::assertEquals('tooltip', $new_ref->creation_metadata->created_on->placement);
        self::assertEquals('relative', $new_ref->creation_metadata->created_on->preference);
        self::assertEquals('en_US', $new_ref->creation_metadata->created_on->locale);
    }

    public function testItUsesTuleapUserInformationForAuthor(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $author = Mockery::mock(\PFUser::class)
            ->shouldReceive(
                [
                    'hasAvatar'    => true,
                    'getAvatarUrl' => '/path/to/avatar',
                ]
            )
            ->getMock();

        $this->user_helper
            ->shouldReceive('getDisplayNameFromUser')
            ->with($author)
            ->andReturn('John Doe');

        $this->commit_metadata_retriever
            ->shouldReceive('getMetadataByRepositoryAndCommits')
            ->andReturn(
                [
                    new CommitMetadata(new CommitStatusUnknown(), $author, $author),
                ]
            );

        $this->commit
            ->shouldReceive('GetHeads')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetTags')
            ->andReturn([]);

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            $this->commit,
            $this->repository,
            $this->user
        );

        self::assertEquals('John Doe', $new_ref->creation_metadata->created_by->display_name);
        self::assertTrue($new_ref->creation_metadata->created_by->has_avatar);
        self::assertEquals('/path/to/avatar', $new_ref->creation_metadata->created_by->avatar_url);
    }

    public function testItUsesCommitAuthorWhenMetadataDoesNotContainTuleapUser(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $this->commit
            ->shouldReceive('GetAuthorName')
            ->andReturn('Korben Dallas');

        $this->commit_metadata_retriever
            ->shouldReceive('getMetadataByRepositoryAndCommits')
            ->andReturn(
                [
                    new CommitMetadata(new CommitStatusUnknown(), null, null),
                ]
            );

        $this->commit
            ->shouldReceive('GetHeads')
            ->andReturn([]);

        $this->commit
            ->shouldReceive('GetTags')
            ->andReturn([]);

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            $this->commit,
            $this->repository,
            $this->user
        );

        self::assertEquals('Korben Dallas', $new_ref->creation_metadata->created_by->display_name);
        self::assertFalse($new_ref->creation_metadata->created_by->has_avatar);
        self::assertEquals('', $new_ref->creation_metadata->created_by->avatar_url);
    }
}
