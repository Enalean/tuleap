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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Authorization\UserCannotMergePullRequestException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestCloser;
use URLVerification;

final class StatusPatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var StatusPatcher
     */
    private $patcher;

    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var GitRepositoryFactory|Mockery\MockInterface
     */
    private $git_repository_factory;

    /**
     * @var Mockery\MockInterface|URLVerification
     */
    private $url_verification;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PullRequestPermissionChecker
     */
    private $pull_request_permissions_checker;

    /**
     * @var Mockery\MockInterface|AccessControlVerifier
     */
    private $access_control_verifier;

    /**
     * @var Mockery\MockInterface|PullRequestCloser
     */
    private $pull_request_closer;

    /**
     * @var Mockery\MockInterface|Project
     */
    private $project_source;

    /**
     * @var GitRepository|Mockery\MockInterface
     */
    private $repository_source;

    /**
     * @var GitRepository|Mockery\MockInterface
     */
    private $repository_destination;
    /**
     * @var Mockery\MockInterface|Project
     */
    private $project_destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_repository_factory           = Mockery::mock(GitRepositoryFactory::class);
        $this->access_control_verifier          = Mockery::mock(AccessControlVerifier::class);
        $this->pull_request_permissions_checker = Mockery::mock(PullRequestPermissionChecker::class);
        $this->pull_request_closer              = Mockery::mock(PullRequestCloser::class);
        $this->url_verification                 = Mockery::mock(URLVerification::class);
        $logger                                 = Mockery::mock(\Psr\Log\LoggerInterface::class);

        $this->patcher = new StatusPatcher(
            $this->git_repository_factory,
            $this->access_control_verifier,
            $this->pull_request_permissions_checker,
            $this->pull_request_closer,
            $this->url_verification,
            $logger
        );

        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(102);

        $this->project_source    = Mockery::mock(Project::class);
        $this->repository_source = Mockery::mock(GitRepository::class);
        $this->repository_source->shouldReceive('getProject')
            ->atMost()
            ->once()
            ->andReturn($this->project_source);

        $this->git_repository_factory->shouldReceive('getRepositoryById')
            ->with(2)
            ->once()
            ->andReturn($this->repository_source);

        $this->project_destination    = Mockery::mock(Project::class);
        $this->repository_destination = Mockery::mock(GitRepository::class);
        $this->repository_destination->shouldReceive('getProject')
            ->atMost()
            ->once()
            ->andReturn($this->project_destination);

        $this->git_repository_factory->shouldReceive('getRepositoryById')
            ->with(1)
            ->once()
            ->andReturn($this->repository_destination);
    }

    public function testItAbandonsAPullRequest()
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanAccessProject($this->project_source);
        $this->mockUserCanAccessProject($this->project_destination);

        $this->mockUserCanWrite($this->repository_source, 'fork01');
        $this->mockUserCanWrite($this->repository_destination, 'master');

        $this->pull_request_closer->shouldReceive('abandon')
            ->with($pull_request, $this->user)
            ->once();

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCanAbandonAPullRequestIfItCanOnlyWriteInSourceRepository()
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanAccessProject($this->project_source);
        $this->mockUserCanAccessProject($this->project_destination);

        $this->mockUserCanWrite($this->repository_source, 'fork01');
        $this->mockUserCannotWrite($this->repository_destination, 'master');

        $this->pull_request_closer->shouldReceive('abandon')
            ->with($pull_request, $this->user)
            ->once();

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCanAbandonAPullRequestIfItCanOnlyWriteInDestinationRepository()
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanAccessProject($this->project_source);
        $this->mockUserCanAccessProject($this->project_destination);

        $this->mockUserCannotWrite($this->repository_source, 'fork01');
        $this->mockUserCanWrite($this->repository_destination, 'master');

        $this->pull_request_closer->shouldReceive('abandon')
            ->with($pull_request, $this->user)
            ->once();

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCannotAbandonAPullRequestIfItCannotWriteInBothSourceAndDestination()
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanAccessProject($this->project_source);
        $this->mockUserCanAccessProject($this->project_destination);

        $this->mockUserCannotWrite($this->repository_source, 'fork01');
        $this->mockUserCannotWrite($this->repository_destination, 'master');

        $this->pull_request_closer->shouldReceive('abandon')
            ->with($pull_request, $this->user)
            ->never();

        $this->expectException(RestException::class);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCannotAbandonAPullRequestIfItCannotAccessSourceProject()
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCannotAccessProject($this->project_source);

        $this->pull_request_closer->shouldReceive('abandon')
            ->with($pull_request)
            ->never();

        $this->expectException(RestException::class);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testUserCannotAbandonAPullRequestIfItCannotAccessDestinationProject()
    {
        $pull_request = $this->buildAPullRequest();

        $this->mockUserCanAccessProject($this->project_source);
        $this->mockUserCannotAccessProject($this->project_destination);

        $this->pull_request_closer->shouldReceive('abandon')
            ->with($pull_request)
            ->never();

        $this->expectException(RestException::class);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'abandon'
        );
    }

    public function testItMergesAPullrequest(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->pull_request_permissions_checker->shouldReceive('checkPullRequestIsMergeableByUser');

        $this->pull_request_closer->shouldReceive('doMerge')
            ->with(
                $this->repository_destination,
                $pull_request,
                $this->user
            )
            ->once();

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'merge'
        );
    }

    public function testUserThatCannotAPullRequestIsRejected(): void
    {
        $pull_request = $this->buildAPullRequest();

        $this->pull_request_permissions_checker->shouldReceive('checkPullRequestIsMergeableByUser')
            ->andThrow(new UserCannotMergePullRequestException($pull_request, $this->user));

        $this->pull_request_closer->shouldReceive('doMerge')
            ->with(
                $this->repository_destination,
                $pull_request,
                $this->user
            )
            ->never();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'merge'
        );
    }

    public function testItThrowsAnExceptionIfStatusIsNotKnown()
    {
        $pull_request = $this->buildAPullRequest();

        $this->expectException(RestException::class);

        $this->patcher->patchStatus(
            $this->user,
            $pull_request,
            'unknown'
        );
    }

    private function buildAPullRequest(): PullRequest
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
        $destination_reference      = 'master';
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
            $destination_reference_sha1
        );
    }

    private function mockUserCanAccessProject(Project $project): void
    {
        $this->url_verification->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once()
            ->andReturnTrue();
    }

    private function mockUserCannotAccessProject(Project $project): void
    {
        $this->url_verification->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once()
            ->andThrow(Mockery::mock(RestException::class));
    }

    private function mockUserCanWrite(GitRepository $repository, string $reference): void
    {
        $this->access_control_verifier->shouldReceive('canWrite')
            ->with(
                $this->user,
                $repository,
                $reference
            )
            ->atMost()
            ->once()
            ->andReturnTrue();
    }

    private function mockUserCannotWrite(GitRepository $repository, string $reference): void
    {
        $this->access_control_verifier->shouldReceive('canWrite')
            ->with(
                $this->user,
                $repository,
                $reference
            )
            ->atMost()
            ->once()
            ->andReturnFalse();
    }
}
