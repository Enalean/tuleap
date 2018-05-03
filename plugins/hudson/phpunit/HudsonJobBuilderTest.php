<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Hudson;

require_once __DIR__ . '/bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HudsonJobBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        $GLOBALS['Language'] = \Mockery::spy(\BaseLanguage::class);
    }

    protected function tearDown()
    {
        unset($GLOBALS['Language']);
    }

    /**
     * @expectedException \HudsonJobURLFileNotFoundException
     */
    public function testExceptionIsRaisedWhenDataCannotBeRetrieved()
    {
        $minimal_job = \Mockery::mock(MinimalHudsonJob::class);
        $minimal_job->shouldReceive('getName');
        $minimal_job->shouldReceive('getJobUrl');

        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('setOption');
        $http_client->shouldReceive('doRequest');
        $http_client->shouldReceive('getLastResponse')->andReturns(false);

        $job_builder = new HudsonJobBuilder($http_client);
        $job_builder->getHudsonJob($minimal_job);
    }

    /**
     * @expectedException \HudsonJobURLFileException
     */
    public function testExceptionIsRaisedWhenInvalidXMLDataIsRetrieved()
    {
        $minimal_job = \Mockery::mock(MinimalHudsonJob::class);
        $minimal_job->shouldReceive('getName');
        $minimal_job->shouldReceive('getJobUrl');

        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('setOption');
        $http_client->shouldReceive('doRequest');
        $http_client->shouldReceive('getLastResponse')->andReturns('Not valid XML');

        $job_builder = new HudsonJobBuilder($http_client);
        $job_builder->getHudsonJob($minimal_job);
    }

    public function testHudsonJobIsRetrieved()
    {
        $minimal_job = \Mockery::mock(MinimalHudsonJob::class);
        $minimal_job->shouldReceive('getName');
        $minimal_job->shouldReceive('getJobUrl');

        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('setOption');
        $http_client->shouldReceive('doRequest');
        $http_client->shouldReceive('getLastResponse')->andReturns('<_/>');

        $job_builder = new HudsonJobBuilder($http_client);
        $job         = $job_builder->getHudsonJob($minimal_job);

        $this->assertInstanceOf(\HudsonJob::class, $job);
    }

    public function testBatchRetrievalTriesToRetrieveAllJobs()
    {
        $minimal_job0 = \Mockery::mock(MinimalHudsonJob::class);
        $minimal_job0->shouldReceive('getName');
        $minimal_job0->shouldReceive('getJobUrl');
        $minimal_job1 = \Mockery::mock(MinimalHudsonJob::class);
        $minimal_job1->shouldReceive('getName');
        $minimal_job1->shouldReceive('getJobUrl');
        $minimal_job2 = \Mockery::mock(MinimalHudsonJob::class);
        $minimal_job2->shouldReceive('getName');
        $minimal_job2->shouldReceive('getJobUrl');

        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('setOption');
        $http_client->shouldReceive('doRequest');
        $http_client->shouldReceive('getLastResponse')->andReturns('<_/>')->times(3);

        $job_builder         = new HudsonJobBuilder($http_client);
        $jobs_with_exception = $job_builder->getHudsonJobsWithException([$minimal_job0, $minimal_job1, $minimal_job2]);

        $this->assertCount(3, $jobs_with_exception);
    }
}
