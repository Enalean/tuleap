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

use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\CommitStatus\CommitStatusWithKnownStatus;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\GitReference\GitPullRequestReference;
use Tuleap\PullRequest\PullRequestWithGitReference;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use Tuleap\PullRequest\ShortStat;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CheckUserCanAccessPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\RetrieveReviewersStub;
use Tuleap\PullRequest\Timeline\SearchAbandonEvent;
use Tuleap\PullRequest\Timeline\SearchMergeEvent;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideUserFromRowStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\User\ProvideUserFromRow;

final class PullRequestRepresentationFactoryTest extends TestCase
{
    /**
     * @var AccessControlVerifier&\PHPUnit\Framework\MockObject\Stub
     */
    private $access_controll_verifier;
    private PullRequestRepresentationFactory $representation_factory;
    private PullRequestWithGitReference $pullrequest_with_reference;
    private \GitRepository $source_repository;
    private \GitRepository $destination_repository;
    /**
     * @var GitExec&\PHPUnit\Framework\MockObject\Stub
     */
    private $git_exec;
    private \PFUser $user;
    private RetrieveReviewersStub $reviwer_dao;
    private ProvideUserFromRow $user_manager;
    private CheckUserCanAccessPullRequestStub $pull_request_permission_checker;

    protected function setUp(): void
    {
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();
        $this->user   = UserTestBuilder::anActiveUser()->withId($pull_request->getUserId())->build();

        $this->access_controll_verifier = $this->createStub(AccessControlVerifier::class);
        $this->git_exec                 = $this->createStub(GitExec::class);
        $this->git_exec->method('getShortStat')->willReturn(new ShortStat(2, 20, 30));

        $commit_status_retriever = $this->createStub(CommitStatusRetriever::class);
        $url_generator           = $this->createStub(GitoliteAccessURLGenerator::class);
        $status_info_builder     = new PullRequestStatusInfoRepresentationBuilder(
            $this->createStub(SearchMergeEvent::class),
            $this->createStub(SearchAbandonEvent::class),
            RetrieveUserByIdStub::withUser($this->user),
        );

        $commit_status_retriever->method('getLastCommitStatus')->willReturn(new CommitStatusWithKnownStatus(CommitStatusWithKnownStatus::STATUS_SUCCESS, new \DateTimeImmutable()));
        $url_generator->method('getHTTPURL')->willReturn("an_http_url/");
        $url_generator->method('getSSHURL')->willReturn("an_ssh_url");

        $purifier = $this->createMock(\Codendi_HTMLPurifier::class);
        $purifier->method('purify')->willReturn("");

        $this->pullrequest_with_reference = new PullRequestWithGitReference(
            $pull_request,
            new GitPullRequestReference(1, GitPullRequestReference::STATUS_OK)
        );
        $this->source_repository          = new \GitRepository();
        $this->destination_repository     = new \GitRepository();

        $reviewer                              = UserTestBuilder::buildWithId(101);
        $reviewer_1                            = UserTestBuilder::buildWithId(102);
        $this->reviwer_dao                     = RetrieveReviewersStub::fromReviewers($reviewer, $reviewer_1);
        $this->user_manager                    = ProvideUserFromRowStub::build();
        $this->pull_request_permission_checker = CheckUserCanAccessPullRequestStub::withAllowed();

        $this->representation_factory = new PullRequestRepresentationFactory(
            $this->access_controll_verifier,
            $commit_status_retriever,
            $url_generator,
            $status_info_builder,
            $purifier,
            $this->createMock(ContentInterpretor::class),
            new ReviewerRetriever($this->user_manager, $this->reviwer_dao, $this->pull_request_permission_checker)
        );


        $this->pullrequest_with_reference = new PullRequestWithGitReference(
            $pull_request,
            new GitPullRequestReference(1, GitPullRequestReference::STATUS_OK)
        );
    }

    public function testItBuildsARepresentationForUserWhoCanMergePullRequest(): void
    {
        $this->access_controll_verifier->method("canWrite")->willReturnOnConsecutiveCalls(true, true, true, true);
        $representation = $this->representation_factory->getPullRequestRepresentation(
            $this->pullrequest_with_reference,
            $this->source_repository,
            $this->destination_repository,
            $this->git_exec,
            $this->user
        );

        self::assertTrue($representation->user_can_merge);
        self::assertTrue($representation->user_can_update_title_and_description);
    }

    public function testItBuildsARepresentationWithReviewers(): void
    {
        $this->access_controll_verifier->method("canWrite")->willReturnOnConsecutiveCalls(true, true, true, true);
        $representation = $this->representation_factory->getPullRequestRepresentation(
            $this->pullrequest_with_reference,
            $this->source_repository,
            $this->destination_repository,
            $this->git_exec,
            $this->user
        );

        self::assertCount(2, $representation->reviewers);
        self::assertSame(101, $representation->reviewers[0]->id);
        self::assertSame(102, $representation->reviewers[1]->id);
    }

    public function testNonIntegratorCanUpdateDescriptionOfHisOwnPullRequest(): void
    {
        $this->access_controll_verifier->method("canWrite")->willReturnOnConsecutiveCalls(false, false, false, false);
        $representation = $this->representation_factory->getPullRequestRepresentation(
            $this->pullrequest_with_reference,
            $this->source_repository,
            $this->destination_repository,
            $this->git_exec,
            $this->user
        );

        self::assertFalse($representation->user_can_merge);
        self::assertTrue($representation->user_can_update_title_and_description);
    }

    public function testItBuildsARepresentationForANonIntegrator(): void
    {
        $user = UserTestBuilder::anActiveUser()->withId(999)->build();

        $this->access_controll_verifier->method("canWrite")->willReturnOnConsecutiveCalls(false, false, false, false);
        $representation = $this->representation_factory->getPullRequestRepresentation(
            $this->pullrequest_with_reference,
            $this->source_repository,
            $this->destination_repository,
            $this->git_exec,
            $user
        );

        self::assertFalse($representation->user_can_merge);
        self::assertFalse($representation->user_can_update_title_and_description);
    }
}
