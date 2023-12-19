<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\PullRequest\GitReference\GitPullRequestReference;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\REST\MinimalUserRepresentation;

final class PullRequestMinimalRepresentationTest extends TestCase
{
    private const PULL_REQUEST_ID         = 21;
    private const TITLE                   = 'Fix phalangiform presubject';
    private const SOURCE_REPO_NAME        = 'glycerate_twinner';
    private const SOURCE_BRANCH_NAME      = 'Phalacrocoracidae';
    private const SOURCE_SHA1             = '7357425451377171515369565166767166476977';
    private const DESTINATION_REPO_NAME   = 'pantherlike_pennant';
    private const DESTINATION_BRANCH_NAME = 'main';
    private const CREATOR_USER_ID         = 172;
    private const CREATOR_NAME            = 'Sarah Rocha';
    private const FIRST_REVIEWER_USER_ID  = 126;
    private const SECOND_REVIEWER_USER_ID = 187;

    private GitoliteAccessURLGenerator & Stub $url_generator;
    private PullRequest $pull_request;
    private MinimalUserRepresentation $pull_request_creator;

    protected function setUp(): void
    {
        $this->url_generator        = $this->createStub(GitoliteAccessURLGenerator::class);
        $this->pull_request_creator = MinimalUserRepresentation::build(
            UserTestBuilder::aUser()->withId(self::CREATOR_USER_ID)->withRealName(self::CREATOR_NAME)->build()
        );

        $this->pull_request = PullRequestTestBuilder::aPullRequestInReview()
            ->withId(self::PULL_REQUEST_ID)
            ->withTitle(self::TITLE)
            ->fromSourceBranch(self::SOURCE_BRANCH_NAME)
            ->fromSourceGitSHA1(self::SOURCE_SHA1)
            ->toDestinationBranch(self::DESTINATION_BRANCH_NAME)
            ->createdBy(self::CREATOR_USER_ID)
            ->createdAt(1428468752)
            ->build();
    }

    private function build(): PullRequestMinimalRepresentation
    {
        $project                = ProjectTestBuilder::aProject()->build();
        $source_repository      = GitRepositoryTestBuilder::aProjectRepository()
            ->withName(self::SOURCE_REPO_NAME)
            ->inProject($project)
            ->build();
        $destination_repository = GitRepositoryTestBuilder::aProjectRepository()
            ->withName(self::DESTINATION_REPO_NAME)
            ->inProject($project)
            ->build();
        $git_reference          = new GitPullRequestReference(20, GitPullRequestReference::STATUS_OK);

        $first_reviewer  = MinimalUserRepresentation::build(
            UserTestBuilder::buildWithId(self::FIRST_REVIEWER_USER_ID)
        );
        $second_reviewer = MinimalUserRepresentation::build(
            UserTestBuilder::buildWithId(self::SECOND_REVIEWER_USER_ID)
        );

        $representation = new PullRequestMinimalRepresentation($this->url_generator);
        $representation->buildMinimal(
            $this->pull_request,
            $source_repository,
            $destination_repository,
            $git_reference,
            $this->pull_request_creator,
            [$first_reviewer, $second_reviewer]
        );
        return $representation;
    }

    public function testItBuilds(): void
    {
        $this->url_generator->method('getHTTPURL')->willReturn('https://example.com/git');
        $this->url_generator->method('getSSHURL')->willReturn('ssh://example.com/git');

        $representation = $this->build();

        self::assertSame(self::PULL_REQUEST_ID, $representation->id);
        self::assertSame('pull_requests/' . self::PULL_REQUEST_ID, $representation->uri);
        self::assertStringContainsString(self::TITLE, $representation->title);
        self::assertSame(self::SOURCE_REPO_NAME, $representation->repository->name);
        self::assertSame(self::SOURCE_BRANCH_NAME, $representation->branch_src);
        self::assertSame(self::DESTINATION_REPO_NAME, $representation->repository_dest->name);
        self::assertSame(self::DESTINATION_BRANCH_NAME, $representation->branch_dest);
        self::assertSame(self::CREATOR_USER_ID, $representation->user_id);
        self::assertSame(self::CREATOR_USER_ID, $representation->creator->id);
        self::assertSame(self::CREATOR_NAME, $representation->creator->real_name);
        self::assertNotNull($representation->creation_date);
        self::assertSame(self::SOURCE_SHA1, $representation->head->id);
        self::assertFalse($representation->is_git_reference_broken);
        self::assertCount(2, $representation->reviewers);
        [$first_reviewer, $second_reviewer] = $representation->reviewers;
        self::assertSame(self::FIRST_REVIEWER_USER_ID, $first_reviewer->id);
        self::assertSame(self::SECOND_REVIEWER_USER_ID, $second_reviewer->id);
    }

    public static function generateStatus(): iterable
    {
        yield [
            PullRequestTestBuilder::anAbandonedPullRequest()->build(),
            PullRequestRepresentation::STATUS_ABANDON,
        ];
        yield [
            PullRequestTestBuilder::aMergedPullRequest()->build(),
            PullRequestRepresentation::STATUS_MERGE,
        ];
        yield [
            PullRequestTestBuilder::aPullRequestInReview()->build(),
            PullRequestRepresentation::STATUS_REVIEW,
        ];
    }

    /**
     * @dataProvider generateStatus
     */
    public function testItExpandsOneLetterStatusToWordStatus(PullRequest $pull_request, string $expected_status): void
    {
        $this->url_generator->method('getHTTPURL')->willReturn('https://example.com/git');
        $this->url_generator->method('getSSHURL')->willReturn('ssh://example.com/git');
        $this->pull_request = $pull_request;

        $representation = $this->build();

        self::assertSame($expected_status, $representation->status);
    }
}
