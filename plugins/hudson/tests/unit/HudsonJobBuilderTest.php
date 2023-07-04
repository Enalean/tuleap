<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Http\Mock\Client;
use HudsonJobURLFileException;
use HudsonJobURLFileNotFoundException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tuleap\GlobalLanguageMock;

final class HudsonJobBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testExceptionIsRaisedWhenThePageCannotBeFound(): void
    {
        $minimal_job = $this->createMock(MinimalHudsonJob::class);
        $minimal_job->method('getName');
        $minimal_job->method('getJobUrl');

        $request_factory = $this->createMock(RequestFactoryInterface::class);
        $request_factory->method('createRequest')->willReturn($this->createMock(RequestInterface::class));

        $http_client = new Client();
        $response    = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $http_client->addResponse($response);

        $job_builder = new HudsonJobBuilder($request_factory, $http_client);

        $this->expectException(HudsonJobURLFileNotFoundException::class);

        $job_builder->getHudsonJob($minimal_job);
    }

    public function testExceptionIsRaisedWhenInvalidXMLDataIsRetrieved(): void
    {
        $minimal_job = $this->createMock(MinimalHudsonJob::class);
        $minimal_job->method('getName');
        $minimal_job->method('getJobUrl');

        $request_factory = $this->createMock(RequestFactoryInterface::class);
        $request_factory->method('createRequest')->willReturn($this->createMock(RequestInterface::class));

        $http_client = new Client();
        $response    = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn('Not valid XML');
        $response->method('getStatusCode')->willReturn(200);
        $http_client->addResponse($response);

        $job_builder = new HudsonJobBuilder($request_factory, $http_client);

        $this->expectException(HudsonJobURLFileException::class);

        $job_builder->getHudsonJob($minimal_job);
    }

    public function testHudsonJobIsRetrieved(): void
    {
        $minimal_job = $this->createMock(MinimalHudsonJob::class);
        $minimal_job->method('getName');
        $minimal_job->method('getJobUrl');

        $request_factory = $this->createMock(RequestFactoryInterface::class);
        $request_factory->method('createRequest')->willReturn($this->createMock(RequestInterface::class));

        $http_client = new Client();
        $response    = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn('<_/>');
        $response->method('getStatusCode')->willReturn(200);
        $http_client->addResponse($response);

        $job_builder = new HudsonJobBuilder($request_factory, $http_client);
        $job         = $job_builder->getHudsonJob($minimal_job);

        self::assertInstanceOf(\HudsonJob::class, $job);
        self::assertCount(1, $http_client->getRequests());
    }

    public function testBatchRetrievalTriesToRetrieveAllJobs(): void
    {
        $minimal_job0 = $this->createMock(MinimalHudsonJob::class);
        $minimal_job0->method('getName');
        $minimal_job0->method('getJobUrl');
        $minimal_job1 = $this->createMock(MinimalHudsonJob::class);
        $minimal_job1->method('getName');
        $minimal_job1->method('getJobUrl');
        $minimal_job2 = $this->createMock(MinimalHudsonJob::class);
        $minimal_job2->method('getName');
        $minimal_job2->method('getJobUrl');

        $request_factory = $this->createMock(RequestFactoryInterface::class);
        $request_factory->method('createRequest')->willReturn($this->createMock(RequestInterface::class));

        $http_client = new Client();
        $response    = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn('<_/>');
        $response->method('getStatusCode')->willReturn(200);
        $http_client->setDefaultResponse($response);

        $job_builder         = new HudsonJobBuilder($request_factory, $http_client);
        $jobs_with_exception = $job_builder->getHudsonJobsWithException([$minimal_job0, $minimal_job1, $minimal_job2]);

        self::assertCount(3, $jobs_with_exception);
        self::assertCount(3, $http_client->getRequests());
    }
}
