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
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;
use Tuleap\HudsonGit\Job\JobDao;
use Tuleap\HudsonGit\Job\ProjectJobDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class LogCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LogCreator $creator;
    /**
     * @var MockObject&JobDao
     */
    private $job_dao;
    /**
     * @var MockObject&ProjectJobDao
     */
    private $project_job_dao;
    private DBTransactionExecutorPassthrough $transaction_executor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job_dao              = $this->createMock(JobDao::class);
        $this->project_job_dao      = $this->createMock(ProjectJobDao::class);
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
            $this->createMock(GitRepository::class),
            0,
            'https://job_url',
            200
        );

        $this->job_dao->expects(self::once())->method('create')->willReturn(1);
        $this->job_dao->expects(self::once())->method('logTriggeredJobs');
        $this->job_dao->expects(self::once())->method('logBranchSource');

        $this->creator->createForRepository($log);
    }

    public function testCreatesLogWithTriggeredJobsOnlyForRepository(): void
    {
        $log = new Log(
            $this->createMock(GitRepository::class),
            0,
            'https://job_url',
            null
        );

        $this->job_dao->expects(self::once())->method('create')->willReturn(1);
        $this->job_dao->expects(self::once())->method('logTriggeredJobs');
        $this->job_dao->expects(self::never())->method('logBranchSource');

        $this->creator->createForRepository($log);
    }

    public function testCreatesLogWithBranchSourceStatusCodeOnlyForRepository(): void
    {
        $log = new Log(
            $this->createMock(GitRepository::class),
            0,
            '',
            200
        );

        $this->job_dao->expects(self::once())->method('create')->willReturn(1);
        $this->job_dao->expects(self::never())->method('logTriggeredJobs');
        $this->job_dao->expects(self::once())->method('logBranchSource');

        $this->creator->createForRepository($log);
    }

    public function testDoesNotCreateAnyLogForRepositoryWhenNothingIsReturned(): void
    {
        $log = new Log(
            $this->createMock(GitRepository::class),
            0,
            '',
            null
        );

        $this->expectException(CannotCreateLogException::class);

        $this->project_job_dao->expects(self::never())->method('create');
        $this->project_job_dao->expects(self::never())->method('logTriggeredJobs');
        $this->project_job_dao->expects(self::never())->method('logBranchSource');

        $this->creator->createForRepository($log);
    }

    public function testCreatesFullLogForProject(): void
    {
        $project        = ProjectTestBuilder::aProject()->withId(101)->build();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            null,
            $project
        );

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getProject')->willReturn($project);
        $repository->method('getId')->willReturn(1);

        $log = new Log(
            $repository,
            0,
            'https://job_url',
            200
        );

        $this->project_job_dao->expects(self::once())->method('create')->willReturn(1);
        $this->project_job_dao->expects(self::once())->method('logTriggeredJobs');
        $this->project_job_dao->expects(self::once())->method('logBranchSource');

        $this->creator->createForProject($jenkins_server, $log);
    }

    public function testCreatesLogWithTriggeredJobsOnlyForProject(): void
    {
        $project        = ProjectTestBuilder::aProject()->withId(101)->build();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            null,
            $project
        );

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getProject')->willReturn($project);
        $repository->method('getId')->willReturn(1);

        $log = new Log(
            $repository,
            0,
            'https://job_url',
            null
        );

        $this->project_job_dao->expects(self::once())->method('create')->willReturn(1);
        $this->project_job_dao->expects(self::once())->method('logTriggeredJobs');
        $this->project_job_dao->expects(self::never())->method('logBranchSource');

        $this->creator->createForProject($jenkins_server, $log);
    }

    public function testCreatesLogWithBranchSourceStatusCodeOnlyForProject(): void
    {
        $project        = ProjectTestBuilder::aProject()->withId(101)->build();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            null,
            $project
        );

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getProject')->willReturn($project);
        $repository->method('getId')->willReturn(1);

        $log = new Log(
            $repository,
            0,
            '',
            200
        );

        $this->project_job_dao->expects(self::once())->method('create')->willReturn(1);
        $this->project_job_dao->expects(self::never())->method('logTriggeredJobs');
        $this->project_job_dao->expects(self::once())->method('logBranchSource');

        $this->creator->createForProject($jenkins_server, $log);
    }

    public function testDoesNotCreateAnyLogForProjectWhenNothingIsReturned(): void
    {
        $project        = ProjectTestBuilder::aProject()->withId(101)->build();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            null,
            $project
        );

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getProject')->willReturn($project);
        $repository->method('getId')->willReturn(1);

        $log = new Log(
            $repository,
            0,
            '',
            null
        );

        $this->expectException(CannotCreateLogException::class);

        $this->project_job_dao->expects(self::never())->method('create');
        $this->project_job_dao->expects(self::never())->method('logTriggeredJobs');
        $this->project_job_dao->expects(self::never())->method('logBranchSource');

        $this->creator->createForProject($jenkins_server, $log);
    }

    public function testDoesNotCreateAnyLogForProjectWhenServerAndLogsAreNotInSameProject(): void
    {
        $project        = ProjectTestBuilder::aProject()->withId(101)->build();
        $jenkins_server = new JenkinsServer(
            1,
            'https://jenkins_url',
            null,
            $project
        );

        $project02  = ProjectTestBuilder::aProject()->withId(102)->build();
        $repository = $this->createMock(GitRepository::class);
        $repository->method('getProject')->willReturn($project02);
        $repository->method('getId')->willReturn(1);

        $log = new Log(
            $repository,
            0,
            '',
            null
        );

        $this->expectException(CannotCreateLogException::class);

        $this->project_job_dao->expects(self::never())->method('create');
        $this->project_job_dao->expects(self::never())->method('logTriggeredJobs');
        $this->project_job_dao->expects(self::never())->method('logBranchSource');

        $this->creator->createForProject($jenkins_server, $log);
    }
}
