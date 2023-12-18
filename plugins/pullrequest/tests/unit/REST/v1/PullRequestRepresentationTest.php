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
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\ShortStat;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\REST\JsonCast;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\User\REST\MinimalUserRepresentation;

final class PullRequestRepresentationTest extends TestCase
{
    private const PULL_REQUEST_ID         = 358;
    private const TITLE                   = 'padella Deuteronomy';
    private const SOURCE_REPO_NAME        = 'hedgewood Feringi';
    private const SOURCE_BRANCH_NAME      = 'chronomantic';
    private const SOURCE_SHA1             = 'a9fda5f347fcb3fdf783f797064128ee360f55cf';
    private const DESTINATION_REPO_NAME   = 'doggerelize gunate';
    private const DESTINATION_BRANCH_NAME = 'main';
    private const DESTINATION_SHA1        = '541b0bd1b9db61b63ed9e6eac6e13cae39d1b9fd';
    private const CREATOR_USER_ID         = 149;
    private const FIRST_REVIERWER_USER_ID = 159;
    private const SECOND_REVIEWER_USER_ID = 134;
    private const SOURCE_DESCRIPTION      = '_reboast gift_';
    private const INTERPRETED_DESCRIPTION = '<em>reboast gift</em>';
    private const GIT_REFERENCE_ID        = 11;
    private const NUMBER_OF_CHANGED_FILES = 6;
    private const PULL_REQUEST_STATUS     = 'merge';
    private GitoliteAccessURLGenerator & Stub $url_generator;
    private PullRequest $pull_request;

    protected function setUp(): void
    {
        $this->url_generator = $this->createStub(GitoliteAccessURLGenerator::class);
        $this->pull_request  = PullRequestTestBuilder::aMergedPullRequest()
            ->withId(self::PULL_REQUEST_ID)
            ->withTitle(self::TITLE)
            ->fromSourceBranch(self::SOURCE_BRANCH_NAME)
            ->fromSourceGitSHA1(self::SOURCE_SHA1)
            ->toDestinationBranch(self::DESTINATION_BRANCH_NAME)
            ->toDestinationGitSHA1(self::DESTINATION_SHA1)
            ->createdBy(self::CREATOR_USER_ID)
            ->withDescription(TimelineComment::FORMAT_MARKDOWN, self::SOURCE_DESCRIPTION)
            ->build();
    }

    private function build(): PullRequestRepresentation
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
        $git_reference          = new GitPullRequestReference(
            self::GIT_REFERENCE_ID,
            GitPullRequestReference::STATUS_OK
        );

        $current_user = UserTestBuilder::buildWithId(150);

        $first_reviewer  = MinimalUserRepresentation::build(
            UserTestBuilder::buildWithId(self::FIRST_REVIERWER_USER_ID)
        );
        $second_reviewer = MinimalUserRepresentation::build(
            UserTestBuilder::buildWithId(self::SECOND_REVIEWER_USER_ID)
        );

        $pull_request_merger = $first_reviewer;

        $representation = new PullRequestRepresentation($this->url_generator);
        $representation->build(
            \Codendi_HTMLPurifier::instance(),
            ContentInterpretorStub::withInterpretedText(self::INTERPRETED_DESCRIPTION),
            $this->pull_request,
            $source_repository,
            $destination_repository,
            $git_reference,
            false,
            true,
            false,
            true,
            PullRequestRepresentationFactory::BUILD_STATUS_SUCCESS,
            1402941022,
            $current_user,
            [$first_reviewer, $second_reviewer],
            new PullRequestShortStatRepresentation(new ShortStat(self::NUMBER_OF_CHANGED_FILES, 131, 103)),
            new PullRequestStatusInfoRepresentation(
                self::PULL_REQUEST_STATUS,
                JsonCast::toDate(1575656856),
                $pull_request_merger
            )
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
        self::assertNotNull($representation->creation_date);
        self::assertSame(self::SOURCE_SHA1, $representation->head->id);
        self::assertFalse($representation->is_git_reference_broken);
        self::assertCount(2, $representation->reviewers);
        [$first_reviewer, $second_reviewer] = $representation->reviewers;
        self::assertSame(self::FIRST_REVIERWER_USER_ID, $first_reviewer->id);
        self::assertSame(self::SECOND_REVIEWER_USER_ID, $second_reviewer->id);
        self::assertSame(self::SOURCE_DESCRIPTION, $representation->description);
        self::assertSame(TimelineComment::FORMAT_MARKDOWN, $representation->description_format);
        self::assertSame(self::INTERPRETED_DESCRIPTION, $representation->post_processed_description);
        self::assertSame(self::SOURCE_SHA1, $representation->reference_src);
        self::assertSame(self::DESTINATION_SHA1, $representation->reference_dest);
        self::assertSame(
            GitPullRequestReference::PR_NAMESPACE . self::GIT_REFERENCE_ID . '/head',
            $representation->head_reference
        );
        self::assertSame(PullRequestRepresentationFactory::BUILD_STATUS_SUCCESS, $representation->last_build_status);
        self::assertNotNull($representation->last_build_date);
        self::assertTrue($representation->user_can_update_labels);
        self::assertFalse($representation->user_can_update_title_and_description);
        self::assertFalse($representation->user_can_merge);
        self::assertTrue($representation->user_can_abandon);
        self::assertFalse($representation->user_can_reopen);
        self::assertSame(self::NUMBER_OF_CHANGED_FILES, $representation->short_stat->files_changed);
        self::assertSame(self::PULL_REQUEST_STATUS, $representation->status_info?->status_type);
        self::assertSame(self::TITLE, $representation->raw_title);
        self::assertSame(self::SOURCE_DESCRIPTION, $representation->description);
        self::assertSame(
            sprintf('pull_requests/%d/comments', self::PULL_REQUEST_ID),
            $representation->resources['comments']['uri']
        );
        self::assertSame(
            sprintf('pull_requests/%d/inline-comments', self::PULL_REQUEST_ID),
            $representation->resources['inline-comments']['uri']
        );
        self::assertSame(
            sprintf('pull_requests/%d/labels', self::PULL_REQUEST_ID),
            $representation->resources['labels']['uri']
        );
        self::assertSame(
            sprintf('pull_requests/%d/files', self::PULL_REQUEST_ID),
            $representation->resources['files']['uri']
        );
        self::assertSame(
            sprintf('pull_requests/%d/file_diff', self::PULL_REQUEST_ID),
            $representation->resources['file_diff']['uri']
        );
        self::assertSame(
            sprintf('pull_requests/%d/timeline', self::PULL_REQUEST_ID),
            $representation->resources['timeline']['uri']
        );
    }

    public static function generatePullRequestStatus(): iterable
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
     * @dataProvider generatePullRequestStatus
     */
    public function testItExpandsOneLetterStatusToWordStatus(PullRequest $pull_request, string $expected_status): void
    {
        $this->url_generator->method('getHTTPURL')->willReturn('https://example.com/git');
        $this->url_generator->method('getSSHURL')->willReturn('ssh://example.com/git');
        $this->pull_request = $pull_request;

        $representation = $this->build();

        self::assertSame($expected_status, $representation->status);
    }

    public static function generateMergeStatus(): iterable
    {
        yield [
            PullRequestTestBuilder::aPullRequestInReview()->withMergeStatus(PullRequest::NO_FASTFORWARD_MERGE)->build(),
            PullRequestRepresentation::NO_FASTFORWARD_MERGE,
        ];
        yield [
            PullRequestTestBuilder::aPullRequestInReview()->withMergeStatus(PullRequest::FASTFORWARD_MERGE)->build(),
            PullRequestRepresentation::FASTFORWARD_MERGE,
        ];
        yield [
            PullRequestTestBuilder::aPullRequestInReview()->withMergeStatus(PullRequest::CONFLICT_MERGE)->build(),
            PullRequestRepresentation::CONFLICT_MERGE,
        ];
        yield [
            PullRequestTestBuilder::aPullRequestInReview()->withMergeStatus(PullRequest::UNKNOWN_MERGE)->build(),
            PullRequestRepresentation::UNKNOWN_MERGE,
        ];
    }

    /**
     * @dataProvider generateMergeStatus
     */
    public function testItExpandsIntMergeStatusToWordMergeStatus(
        PullRequest $pull_request,
        string $expected_merge_status,
    ): void {
        $this->url_generator->method('getHTTPURL')->willReturn('https://example.com/git');
        $this->url_generator->method('getSSHURL')->willReturn('ssh://example.com/git');
        $this->pull_request = $pull_request;

        $representation = $this->build();

        self::assertSame($expected_merge_status, $representation->merge_status);
    }
}
