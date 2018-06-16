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

use Http\Client\Exception\HttpException;
use Http\Message\RequestFactory;
use Http\Mock\Client;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
     * @expectedException \Http\Client\Exception\HttpException
     */
    public function testExceptionIsRaisedWhenDataCannotBeRetrieved()
    {
        $minimal_job = \Mockery::mock(MinimalHudsonJob::class);
        $minimal_job->shouldReceive('getName');
        $minimal_job->shouldReceive('getJobUrl');

        $request_factory = \Mockery::mock(RequestFactory::class);
        $request_factory->shouldReceive('createRequest')->andReturns(\Mockery::mock(RequestInterface::class));

        $http_client = new Client();
        $http_client->addException(\Mockery::mock(HttpException::class));

        $job_builder = new HudsonJobBuilder($request_factory, $http_client);
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

        $request_factory = \Mockery::mock(RequestFactory::class);
        $request_factory->shouldReceive('createRequest')->andReturns(\Mockery::mock(RequestInterface::class));

        $http_client = new Client();
        $response    = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturns('Not valid XML');
        $http_client->addResponse($response);

        $job_builder = new HudsonJobBuilder($request_factory, $http_client);
        $job_builder->getHudsonJob($minimal_job);
    }

    public function testHudsonJobIsRetrieved()
    {
        $minimal_job = \Mockery::mock(MinimalHudsonJob::class);
        $minimal_job->shouldReceive('getName');
        $minimal_job->shouldReceive('getJobUrl');

        $request_factory = \Mockery::mock(RequestFactory::class);
        $request_factory->shouldReceive('createRequest')->andReturns(\Mockery::mock(RequestInterface::class));

        $http_client = new Client();
        $response    = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturns('<_/>');
        $http_client->addResponse($response);

        $job_builder = new HudsonJobBuilder($request_factory, $http_client);
        $job         = $job_builder->getHudsonJob($minimal_job);

        $this->assertInstanceOf(\HudsonJob::class, $job);
        $this->assertCount(1, $http_client->getRequests());
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

        $request_factory = \Mockery::mock(RequestFactory::class);
        $request_factory->shouldReceive('createRequest')->andReturns(\Mockery::mock(RequestInterface::class));

        $http_client = new Client();
        $response    = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturns('<_/>');
        $http_client->setDefaultResponse($response);

        $job_builder         = new HudsonJobBuilder($request_factory, $http_client);
        $jobs_with_exception = $job_builder->getHudsonJobsWithException([$minimal_job0, $minimal_job1, $minimal_job2]);

        $this->assertCount(3, $jobs_with_exception);
        $this->assertCount(3, $http_client->getRequests());
    }
}
