<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Git\REST\v1;

use DateTimeImmutable;
use GitRepository;
use Tuleap\Git\CommitMetadata\CommitMetadata;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\CommitStatus\CommitStatusUnknown;
use Tuleap\Git\CommitStatus\CommitStatusWithKnownStatus;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitCommitRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \Git_GitRepositoryUrlManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $url_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CommitMetadataRetriever
     */
    private $metadata_retriever;

    /**
     * @var GitCommitRepresentationBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private GitCommitRepresentationBuilder $git_commit_representation_builder;

    protected function setUp(): void
    {
        $this->metadata_retriever = $this->createMock(CommitMetadataRetriever::class);
        $this->url_manager        = $this->createMock(\Git_GitRepositoryUrlManager::class);

        $this->git_commit_representation_builder = $this
            ->getMockBuilder(GitCommitRepresentationBuilder::class)
            ->onlyMethods(['getCommitReferences'])
            ->setConstructorArgs([$this->metadata_retriever, $this->url_manager, ProvideUserAvatarUrlStub::build()])
            ->getMock();
        $this->git_commit_representation_builder->method('getCommitReferences')->willReturn([]);

        $user_helper_mock = $this->createMock(UserHelper::class);
        $user_helper_mock->method('getUserUrl')->willReturn('https://user.example.com');
        $user_helper_mock->method('getDisplayNameFromUser')->willReturn('asticotc');
        UserHelper::setInstance($user_helper_mock);
    }

    protected function tearDown(): void
    {
        UserHelper::clearInstance();
    }

    public function testItBuildTheCommitRepresentationWithAuthorMinimalRepresentation(): void
    {
        $commit_status = new CommitStatusUnknown();
        $author        = new \PFUser(
            ['language_id' => 'en', 'user_id' => 102, 'user_name' => 'asticotc', 'realname' => 'Coco L\'asticot']
        );
        $committer     = new \PFUser(['language_id' => 'en', 'user_id' => 101]);

        $repository = new GitRepository();

        $all_metadata   = [];
        $metadata       = new CommitMetadata($commit_status, $author, $committer);
        $all_metadata[] = $metadata;

        $commit = $this->createMock(Commit::class);
        $commit->method('GetComment')->willReturn(['Change the Merlin name']);
        $commit->method('getSignature')->willReturn('m3rl1n');
        $commit->method('GetHash')->willReturn('jh4sh');
        $commit->method('GetTitle')->willReturn('kamelot');
        $commit->method('GetAuthorName')->willReturn('Arthur');
        $commit->method('getAuthorEmail')->willReturn('king@email.example.com');
        $commit->method('GetAuthorEpoch')->willReturn('548412');
        $commit->method('GetCommitterEpoch')->willReturn('184841');

        $this->metadata_retriever->method('getMetadataByRepositoryAndCommits')->with($repository, $commit)->willReturn(
            $all_metadata
        );

        $this->url_manager->method('getRepositoryBaseUrl')->with($repository)->willReturn('https://legend.example.git.com');

        $representation = $this->git_commit_representation_builder->build($repository, $commit);

        self::assertEquals('jh4sh', $representation->id);
        self::assertEquals('Arthur', $representation->author_name);
        self::assertEquals('king@email.example.com', $representation->author_email);
        self::assertEquals('1970-01-07T09:20:12+01:00', $representation->authored_date);
        self::assertEquals('1970-01-03T04:20:41+01:00', $representation->committed_date);
        self::assertEquals('kamelot', $representation->title);
        self::assertEquals('Change the Merlin name', $representation->message);
        self::assertInstanceOf(MinimalUserRepresentation::class, $representation->author);
        self::assertEquals('m3rl1n', $representation->verification->signature);
        self::assertNull($representation->commit_status);
    }

    public function testItBuildTheCommitRepresentationWhichContainsCommitStatusMetadata(): void
    {
        $commit_status = new CommitStatusWithKnownStatus(1, new DateTimeImmutable());
        $author        = null;
        $committer     = new \PFUser(['language_id' => 'en', 'user_id' => 101]);

        $repository = new GitRepository();

        $all_metadata   = [];
        $metadata       = new CommitMetadata($commit_status, $author, $committer);
        $all_metadata[] = $metadata;

        $commit = $this->createMock(Commit::class);
        $commit->method('GetComment')->willReturn(['Change the Merlin name']);
        $commit->method('getSignature')->willReturn('m3rl1n');
        $commit->method('GetHash')->willReturn('jh4sh');
        $commit->method('GetTitle')->willReturn('kamelot');
        $commit->method('GetAuthorName')->willReturn('Arthur');
        $commit->method('getAuthorEmail')->willReturn('king@email.example.com');
        $commit->method('GetAuthorEpoch')->willReturn('548412');
        $commit->method('GetCommitterEpoch')->willReturn('184841');

        $this->metadata_retriever->method('getMetadataByRepositoryAndCommits')->with($repository, $commit)->willReturn(
            $all_metadata
        );

        $this->url_manager->method('getRepositoryBaseUrl')->with($repository)->willReturn('https://legend.example.git.com');

        $representation = $this->git_commit_representation_builder->build($repository, $commit);

        self::assertEquals('jh4sh', $representation->id);
        self::assertEquals('Arthur', $representation->author_name);
        self::assertEquals('king@email.example.com', $representation->author_email);
        self::assertEquals('1970-01-07T09:20:12+01:00', $representation->authored_date);
        self::assertEquals('1970-01-03T04:20:41+01:00', $representation->committed_date);
        self::assertEquals('kamelot', $representation->title);
        self::assertEquals('Change the Merlin name', $representation->message);
        self::assertNull($representation->author);
        self::assertEquals('m3rl1n', $representation->verification->signature);
        self::assertEquals('failure', $representation->commit_status->name);
    }

    public function testItBuildTheCommitRepresentation(): void
    {
        $commit_status = new CommitStatusWithKnownStatus(1, new DateTimeImmutable());
        $author        = new \PFUser(
            ['language_id' => 'en', 'user_id' => 102, 'user_name' => 'asticotc', 'realname' => 'Coco L\'asticot']
        );
        $committer     = new \PFUser(['language_id' => 'en', 'user_id' => 101]);

        $repository = new GitRepository();

        $all_metadata   = [];
        $metadata       = new CommitMetadata($commit_status, $author, $committer);
        $all_metadata[] = $metadata;

        $commit = $this->createMock(Commit::class);
        $commit->method('GetComment')->willReturn(['Change the Merlin name']);
        $commit->method('getSignature')->willReturn('m3rl1n');
        $commit->method('GetHash')->willReturn('jh4sh');
        $commit->method('GetTitle')->willReturn('kamelot');
        $commit->method('GetAuthorName')->willReturn('Arthur');
        $commit->method('getAuthorEmail')->willReturn('king@email.example.com');
        $commit->method('GetAuthorEpoch')->willReturn('548412');
        $commit->method('GetCommitterEpoch')->willReturn('184841');

        $this->metadata_retriever->method('getMetadataByRepositoryAndCommits')->with($repository, $commit)->willReturn(
            $all_metadata
        );

        $this->url_manager->method('getRepositoryBaseUrl')->with($repository)->willReturn('https://legend.example.git.com');

        $representation = $this->git_commit_representation_builder->build($repository, $commit);

        self::assertEquals('jh4sh', $representation->id);
        self::assertEquals('Arthur', $representation->author_name);
        self::assertEquals('king@email.example.com', $representation->author_email);
        self::assertEquals('1970-01-07T09:20:12+01:00', $representation->authored_date);
        self::assertEquals('1970-01-03T04:20:41+01:00', $representation->committed_date);
        self::assertEquals('kamelot', $representation->title);
        self::assertEquals('Change the Merlin name', $representation->message);
        self::assertInstanceOf(MinimalUserRepresentation::class, $representation->author);
        self::assertEquals('m3rl1n', $representation->verification->signature);
        self::assertEquals('failure', $representation->commit_status->name);
    }
}
