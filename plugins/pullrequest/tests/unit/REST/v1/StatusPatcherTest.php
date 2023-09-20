<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use GitRepository;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use PFUser;
use Project;
use Psr\Log\NullLogger;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Authorization\UserCannotMergePullRequestException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\PullRequestCloser;
use Tuleap\PullRequest\PullRequestReopener;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use URLVerification;

final class StatusPatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private StatusPatcher $patcher;
    private PFUser $user;

    /**
     * @var GitRepositoryFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $git_repository_factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&URLVerification
     */
    private $url_verification;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestPermissionChecker
     */
    private $pull_request_permissions_checker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessControlVerifier
     */
    private $access_control_verifier;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestCloser
     */
    private $pull_request_closer;
    private Project $project_source;
    /**
     * @var GitRepository&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository_source;
    /**
     * @var GitRepository&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository_destination;
    private Project $project_destination;
    /**
     * @var PullRequestReopener&\PHPUnit\Framework\MockObject\MockObject
     */
    private $reopener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_repository_factory           = $this->createMock(GitRepositoryFactory::class);
        $this->access_control_verifier          = $this->createMock(AccessControlVerifier::class);
        $this->pull_request_permissions_checker = $this->createMock(PullRequestPermissionChecker::class);
        $this->pull_request_closer              = $this->createMock(PullRequestCloser::class);
        $this->url_verification                 = $this->createMock(URLVerification::class);
        $this->reopener                         = $this->createMock(PullRequestReopener::class);

        $this->patcher = new StatusPatcher(
            $this->git_repository_factory,
            $this->access_control_verifier,
            $this->pull_request_permissions_checker,
            $this->pull_request_closer,
            $this->reopener,
            $this->url_verification,
            new NullLogger(),
        );

        $this->user = UserTestBuilder::aUser()->withId(102)->build();

        $this->project_source    = ProjectTestBuilder::aProject()->build();
        $this->repository_source = $this->createMock(GitRepository::class);
        $this->repository_source
            ->expects(self::atMost(1))
            ->method('getProject')
            ->willReturn($this->project_source);

        $this->project_destination    = ProjectTestBuilder::aProject()->build();
        $this->repository_destination = $this->createMock(GitRepository::class);
        $this->repository_destination
            ->expects(self::atMost(1))
            ->method('getProject')
            ->willReturn($this->project_destination);

        $this->git_repository_factory
            ->method('getRepositoryById')
            ->willReturnMap([
                [1, $this->repository_destination],
                [2, $this->repository_source],
            ]);
    }

    public function testItAbandonsAPullRequest(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanAccessBothProjects($this->project_source, $this->project_destination);

        $this->mockUserCanWriteInBothRepositories(
            $this->repository_source,
            'fork01',
            $this->repository_destination,
            'main'
        );

        $this->pull_request_closer
            ->expects(self::once())
            ->method('abandon')
            ->with($pull_request, $this->user);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCanAbandonAPullRequestIfItCanOnlyWriteInSourceRepository(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanAccessBothProjects($this->project_source, $this->project_destination);

        $this->mockUserCanOnlyWriteInSourceRepository(
            $this->repository_source,
            'fork01',
            $this->repository_destination,
            'main'
        );

        $this->pull_request_closer
            ->expects(self::once())
            ->method('abandon')
            ->with($pull_request, $this->user);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCanAbandonAPullRequestIfItCanOnlyWriteInDestinationRepository(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanAccessBothProjects($this->project_source, $this->project_destination);

        $this->mockUserCanOnlyWriteInDestinationRepository(
            $this->repository_source,
            'fork01',
            $this->repository_destination,
            'main'
        );

        $this->pull_request_closer
            ->expects(self::once())
            ->method('abandon')
            ->with($pull_request, $this->user);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCannotAbandonAPullRequestIfItCannotWriteInBothSourceAndDestination(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanAccessBothProjects($this->project_source, $this->project_destination);

        $this->mockUserCannotWriteInBothRepositories(
            $this->repository_source,
            'fork01',
            $this->repository_destination,
            'main',
        );

        $this->pull_request_closer
            ->expects(self::never())
            ->method('abandon')
            ->with($pull_request, $this->user);

        $this->expectException(RestException::class);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCannotAbandonAPullRequestIfItCannotAccessSourceProject(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCannotAccessProject($this->project_source);

        $this->pull_request_closer
            ->expects(self::never())
            ->method('abandon')
            ->with($pull_request);

        $this->expectException(RestException::class);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCannotAbandonAPullRequestIfItCannotAccessDestinationProject(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanOnlyAccessSourceProject($this->project_source, $this->project_destination);

        $this->pull_request_closer
            ->expects(self::never())
            ->method('abandon')
            ->with($pull_request);

        $this->expectException(RestException::class);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon',
        );
    }

    public function testItMergesAPullrequest(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->pull_request_permissions_checker->method('checkPullRequestIsMergeableByUser');

        $this->pull_request_closer
            ->expects(self::once())
            ->method('doMerge')
            ->with(
                $this->repository_destination,
                $pull_request,
                $this->user
            );

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'merge',
        );
    }

    public function testUserThatCannotMergeAPullRequestIsRejected(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->pull_request_permissions_checker->method('checkPullRequestIsMergeableByUser')
            ->willThrowException(new UserCannotMergePullRequestException($pull_request, $this->user));

        $this->pull_request_closer
            ->expects(self::never())
            ->method('doMerge')
            ->with(
                $this->repository_destination,
                $pull_request,
                $this->user
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'merge'
        );
    }

    public function testItReopensAPullrequest(): void
    {
        $pull_request = $this->buildAPullRequest('A');

        $this->mockUserCanAccessBothProjects($this->project_source, $this->project_destination);

        $this->mockUserCanWriteInBothRepositories(
            $this->repository_source,
            'fork01',
            $this->repository_destination,
            'main',
        );

        $this->reopener->expects(self::once())->method("reopen");

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'review'
        );
    }

    public function testItThrowsAnExceptionIfStatusIsNotKnown(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->expectException(RestException::class);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'unknown'
        );
    }

    private function buildAPullRequest(string $status = 'R'): PullRequest
    {
        $id                         = 1;
        $title                      = 'title01';
        $description                = 'descr01';
        $source_repository_id       = 2;
        $destination_repository_id  = 1;
        $user_id                    = 102;
        $creation_date              = 1565169592;
        $source_reference           = 'fork01';
        $source_reference_sha1      = '0000000000000000000000000000000000000000';
        $destination_reference      = 'main';
        $destination_reference_sha1 = '0000000000000000000000000000000000000001';

        return new PullRequest(
            $id,
            $title,
            $description,
            $source_repository_id,
            $user_id,
            $creation_date,
            $source_reference,
            $source_reference_sha1,
            $destination_repository_id,
            $destination_reference,
            $destination_reference_sha1,
            TimelineComment::FORMAT_TEXT,
            $status
        );
    }

    private function mockUserCanAccessBothProjects(Project $source_project, Project $destination_project): void
    {
        $this->url_verification
            ->method('userCanAccessProject')
            ->willReturnMap([
                [$this->user, $source_project, true],
                [$this->user, $destination_project, true],
            ]);
    }

    private function mockUserCanOnlyAccessSourceProject(Project $source_project, Project $destination_project): void
    {
        $this->url_verification
            ->method('userCanAccessProject')
            ->willReturnCallback(
                function (PFUser $user_param, Project $project_param) use ($source_project, $destination_project): bool {
                    if ($user_param === $this->user && $project_param === $source_project) {
                        return true;
                    } elseif ($user_param === $this->user && $project_param === $destination_project) {
                        throw new RestException(401, '');
                    }

                    return false;
                }
            );
    }

    private function mockUserCannotAccessProject(Project $project): void
    {
        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $project)
            ->willThrowException($this->createMock(RestException::class));
    }

    private function mockUserCanWriteInBothRepositories(
        GitRepository $source_repository,
        string $source_reference,
        GitRepository $destination_repository,
        string $destination_reference,
    ): void {
        $this->access_control_verifier
            ->method('canWrite')
            ->willReturnMap([
                [$this->user, $source_repository, $source_reference, true],
                [$this->user, $destination_repository, $destination_reference, true],
            ]);
    }

    private function mockUserCannotWriteInBothRepositories(
        GitRepository $source_repository,
        string $source_reference,
        GitRepository $destination_repository,
        string $destination_reference,
    ): void {
        $this->access_control_verifier
            ->method('canWrite')
            ->willReturnMap([
                [$this->user, $source_repository, $source_reference, false],
                [$this->user, $destination_repository, $destination_reference, false],
            ]);
    }

    private function mockUserCanOnlyWriteInSourceRepository(
        GitRepository $source_repository,
        string $source_reference,
        GitRepository $destination_repository,
        string $destination_reference,
    ): void {
        $this->access_control_verifier
            ->method('canWrite')
            ->willReturnMap([
                [$this->user, $source_repository, $source_reference, true],
                [$this->user, $destination_repository, $destination_reference, false],
            ]);
    }

    private function mockUserCanOnlyWriteInDestinationRepository(
        GitRepository $source_repository,
        string $source_reference,
        GitRepository $destination_repository,
        string $destination_reference,
    ): void {
        $this->access_control_verifier
            ->method('canWrite')
            ->willReturnMap([
                [$this->user, $source_repository, $source_reference, false],
                [$this->user, $destination_repository, $destination_reference, true],
            ]);
    }
}
