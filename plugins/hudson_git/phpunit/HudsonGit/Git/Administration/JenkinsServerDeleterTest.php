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

namespace Tuleap\HudsonGit\Git\Administration;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\HudsonGit\Job\ProjectJobDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class JenkinsServerDeleterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var JenkinsServerDeleter
     */
    private $deleter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JenkinsServerDao
     */
    private $jenkins_server_dao;

    /**
     * @var JenkinsServer
     */
    private $jenkins_server;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectJobDao
     */
    private $project_job_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jenkins_server_dao = Mockery::mock(JenkinsServerDao::class);
        $this->project_job_dao    = Mockery::mock(ProjectJobDao::class);

        $this->deleter = new JenkinsServerDeleter(
            $this->jenkins_server_dao,
            $this->project_job_dao,
            new DBTransactionExecutorPassthrough()
        );

        $this->jenkins_server = new JenkinsServer(
            1,
            'url',
            Mockery::mock(Project::class)
        );
    }

    public function testItDeletesAJenkinsServer(): void
    {
        $this->project_job_dao->shouldReceive('deleteLogsOfServer')->with(1)->once();
        $this->jenkins_server_dao->shouldReceive('deleteJenkinsServer')->with(1)->once();

        $this->deleter->deleteServer($this->jenkins_server);
    }
}
