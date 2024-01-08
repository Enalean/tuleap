<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\CommitStatus\CommitStatusWithKnownStatus;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\GitReference\GitPullRequestReference;
use Tuleap\PullRequest\PullRequestWithGitReference;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use Tuleap\PullRequest\ShortStat;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CheckUserCanAccessPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\SearchReviewersStub;
use Tuleap\PullRequest\Timeline\SearchAbandonEvent;
use Tuleap\PullRequest\Timeline\SearchMergeEvent;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideUserFromRowStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class PullRequestRepresentationFactoryTest extends TestCase
{
    private const FIRST_REVIEWER_USER_ID  = 101;
    private const SECOND_REVIEWER_USER_ID = 102;
    private const CREATOR_USER_ID         = 172;
    private AccessControlVerifier & Stub $access_control_verifier;
    private \PFUser $current_user;
    private RetrieveUserByIdStub $user_retriever;

    protected function setUp(): void
    {
        $this->current_user = UserTestBuilder::anActiveUser()->withId(self::CREATOR_USER_ID)->build();

        $this->access_control_verifier = $this->createStub(AccessControlVerifier::class);
        $this->user_retriever          = RetrieveUserByIdStub::withUser($this->current_user);
    }

    /**
     * @throws \Git_Command_Exception
     * @throws RestException
     */
    private function getRepresentation(): PullRequestRepresentation
    {
        $pull_request               = PullRequestTestBuilder::aPullRequestInReview()
            ->createdBy(self::CREATOR_USER_ID)
            ->build();
        $source_repository          = GitRepositoryTestBuilder::aProjectRepository()->build();
        $destination_repository     = GitRepositoryTestBuilder::aProjectRepository()->build();
        $pullrequest_with_reference = new PullRequestWithGitReference(
            $pull_request,
            new GitPullRequestReference(1, GitPullRequestReference::STATUS_OK)
        );

        $first_reviewer  = UserTestBuilder::buildWithId(self::FIRST_REVIEWER_USER_ID);
        $second_reviewer = UserTestBuilder::buildWithId(self::SECOND_REVIEWER_USER_ID);

        $commit_status_retriever = $this->createStub(CommitStatusRetriever::class);
        $commit_status_retriever->method('getLastCommitStatus')->willReturn(
            new CommitStatusWithKnownStatus(CommitStatusWithKnownStatus::STATUS_SUCCESS, new \DateTimeImmutable())
        );

        $url_generator = $this->createStub(GitoliteAccessURLGenerator::class);
        $url_generator->method('getHTTPURL')->willReturn('https://example.com/git');
        $url_generator->method('getSSHURL')->willReturn('ssh://example.com/git');

        $status_info_builder = new PullRequestStatusInfoRepresentationBuilder(
            $this->createStub(SearchMergeEvent::class),
            $this->createStub(SearchAbandonEvent::class),
            RetrieveUserByIdStub::withNoUser(),
        );

        $git_exec = $this->createStub(GitExec::class);
        $git_exec->method('getShortStat')->willReturn(new ShortStat(2, 20, 30));

        $representation_factory = new PullRequestRepresentationFactory(
            $this->access_control_verifier,
            $commit_status_retriever,
            $url_generator,
            $status_info_builder,
            \Codendi_HTMLPurifier::instance(),
            $this->createMock(ContentInterpretor::class),
            new ReviewerRetriever(
                ProvideUserFromRowStub::build(),
                SearchReviewersStub::fromReviewers($first_reviewer, $second_reviewer),
                CheckUserCanAccessPullRequestStub::withAllowed()
            ),
            $this->user_retriever
        );

        return $representation_factory->getPullRequestRepresentation(
            $pullrequest_with_reference,
            $source_repository,
            $destination_repository,
            $git_exec,
            $this->current_user
        );
    }

    public function testItBuildsARepresentationForUserWhoCanMergePullRequest(): void
    {
        $this->access_control_verifier->method('canWrite')->willReturnOnConsecutiveCalls(true, true, true, true);
        $representation = $this->getRepresentation();

        self::assertTrue($representation->user_can_merge);
        self::assertTrue($representation->user_can_update_title_and_description);
    }

    public function testItBuildsARepresentationWithReviewers(): void
    {
        $this->access_control_verifier->method('canWrite')->willReturnOnConsecutiveCalls(true, true, true, true);
        $representation = $this->getRepresentation();

        self::assertCount(2, $representation->reviewers);
        self::assertSame(self::FIRST_REVIEWER_USER_ID, $representation->reviewers[0]->id);
        self::assertSame(self::SECOND_REVIEWER_USER_ID, $representation->reviewers[1]->id);
    }

    public function testNonIntegratorCanUpdateDescriptionOfHisOwnPullRequest(): void
    {
        $this->access_control_verifier->method('canWrite')->willReturnOnConsecutiveCalls(false, false, false, false);
        $representation = $this->getRepresentation();

        self::assertFalse($representation->user_can_merge);
        self::assertTrue($representation->user_can_update_title_and_description);
    }

    public function testItBuildsARepresentationForANonIntegrator(): void
    {
        $this->current_user = UserTestBuilder::anActiveUser()->withId(999)->build();

        $this->access_control_verifier->method('canWrite')->willReturnOnConsecutiveCalls(false, false, false, false);
        $representation = $this->getRepresentation();

        self::assertFalse($representation->user_can_merge);
        self::assertFalse($representation->user_can_update_title_and_description);
    }

    public function testItThrowsError500WhenItCannotFindUserWhoCreatedThePullRequest(): void
    {
        $this->user_retriever = RetrieveUserByIdStub::withNoUser();
        // Tuleap is never supposed to delete rows from the User table.
        // When this case happens, there is a problem with stored data.
        $this->expectExceptionCode(500);
        $this->expectException(RestException::class);
        $this->getRepresentation();
    }
}
