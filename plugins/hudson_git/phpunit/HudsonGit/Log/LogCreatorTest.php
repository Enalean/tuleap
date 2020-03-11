<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Log;

use GitRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;
use Tuleap\HudsonGit\Job\JobDao;
use Tuleap\HudsonGit\Job\ProjectJobDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class LogCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var LogCreator
     */
    private $creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JobDao
     */
    private $job_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectJobDao
     */
    private $project_job_dao;

    /**
     * @var DBTransactionExecutorPassthrough
     */
    private $transaction_executor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job_dao              = Mockery::mock(JobDao::class);
        $this->project_job_dao      = Mockery::mock(ProjectJobDao::class);
        $this->transaction_executor = new DBTransactionExecutorPassthrough();

        $this->creator = new LogCreator(
            $this->job_dao,
            $this->project_job_dao,
            $this->transaction_executor
        );
    }

    public function testCreatesFullLogForRepository(): void
    {
        $log = new Log(
            Mockery::mock(GitRepository::class),
            0,
            'https://job_url',
            200
        );

        $this->job_dao->shouldReceive('create')->once()->andReturn(1);
        $this->job_dao->shouldReceive('logTriggeredJobs')->once();
        $this->job_dao->shouldReceive('logBranchSource')->once();

        $this->creator->createForRepository($log);
    }

    public function testCreatesLogWithTriggeredJobsOnlyForRepository(): void
    {
        $log = new Log(
            Mockery::mock(GitRepository::class),
            0,
            'https://job_url',
            null
        );

        $this->job_dao->shouldReceive('create')->once()->andReturn(1);
        $this->job_dao->shouldReceive('logTriggeredJobs')->once();
        $this->job_dao->shouldReceive('logBranchSource')->never();

        $this->creator->createForRepository($log);
    }

    public function testCreatesLogWithBranchSourceStatusCodeOnlyForRepository(): void
    {
        $log = new Log(
            Mockery::mock(GitRepository::class),
            0,
            '',
            200
        );

        $this->job_dao->shouldReceive('create')->once()->andReturn(1);
        $this->job_dao->shouldReceive('logTriggeredJobs')->never();
        $this->job_dao->shouldReceive('logBranchSource')->once();

        $this->creator->createForRepository($log);
    }

    public function testDoesNotCreateAnyLogForRepositoryWhenNothingIsReturned(): void
    {
        $log = new Log(
            Mockery::mock(GitRepository::class),
            0,
            '',
            null
        );

        $this->expectException(CannotCreateLogException::class);

        $this->job_dao->shouldReceive('create')->never();
        $this->job_dao->shouldReceive('logTriggeredJobs')->never();
        $this->job_dao->shouldReceive('logBranchSource')->never();

        $this->creator->createForRepository($log);
    }

    public function testCreatesFullLogForProject(): void
    {
        $project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(101)->getMock();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            $project
        );

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturn($project);
        $repository->shouldReceive('getId')->andReturn(1);

        $log = new Log(
            $repository,
            0,
            'https://job_url',
            200
        );

        $this->project_job_dao->shouldReceive('create')->once()->andReturn(1);
        $this->project_job_dao->shouldReceive('logTriggeredJobs')->once();
        $this->project_job_dao->shouldReceive('logBranchSource')->once();

        $this->creator->createForProject($jenkins_server, $log);
    }

    public function testCreatesLogWithTriggeredJobsOnlyForProject(): void
    {
        $project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(101)->getMock();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            $project
        );

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturn($project);
        $repository->shouldReceive('getId')->andReturn(1);

        $log = new Log(
            $repository,
            0,
            'https://job_url',
            null
        );

        $this->project_job_dao->shouldReceive('create')->once()->andReturn(1);
        $this->project_job_dao->shouldReceive('logTriggeredJobs')->once();
        $this->project_job_dao->shouldReceive('logBranchSource')->never();

        $this->creator->createForProject($jenkins_server, $log);
    }

    public function testCreatesLogWithBranchSourceStatusCodeOnlyForProject(): void
    {
        $project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(101)->getMock();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            $project
        );

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturn($project);
        $repository->shouldReceive('getId')->andReturn(1);

        $log = new Log(
            $repository,
            0,
            '',
            200
        );

        $this->project_job_dao->shouldReceive('create')->once()->andReturn(1);
        $this->project_job_dao->shouldReceive('logTriggeredJobs')->never();
        $this->project_job_dao->shouldReceive('logBranchSource')->once();

        $this->creator->createForProject($jenkins_server, $log);
    }

    public function testDoesNotCreateAnyLogForProjectWhenNothingIsReturned(): void
    {
        $project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(101)->getMock();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            $project
        );

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturn($project);
        $repository->shouldReceive('getId')->andReturn(1);

        $log = new Log(
            $repository,
            0,
            '',
            null
        );

        $this->expectException(CannotCreateLogException::class);

        $this->project_job_dao->shouldReceive('create')->never();
        $this->project_job_dao->shouldReceive('logTriggeredJobs')->never();
        $this->project_job_dao->shouldReceive('logBranchSource')->never();

        $this->creator->createForProject($jenkins_server, $log);
    }

    public function testDoesNotCreateAnyLogForProjectWhenServerAndLogsAreNotInSameProject(): void
    {
        $project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(101)->getMock();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            $project
        );

        $project02 = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn(102)->getMock();
        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturn($project02);
        $repository->shouldReceive('getId')->andReturn(1);

        $log = new Log(
            $repository,
            0,
            '',
            null
        );

        $this->expectException(CannotCreateLogException::class);

        $this->project_job_dao->shouldReceive('create')->never();
        $this->project_job_dao->shouldReceive('logTriggeredJobs')->never();
        $this->project_job_dao->shouldReceive('logBranchSource')->never();

        $this->creator->createForProject($jenkins_server, $log);
    }
}
