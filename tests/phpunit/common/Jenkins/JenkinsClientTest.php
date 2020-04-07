<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Jenkins;

use Exception;
use Http\Mock\Client;
use Jenkins_Client;
use Jenkins_ClientUnableToLaunchBuildException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class JenkinsClientTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|JenkinsCSRFCrumbRetriever
     */
    private $jenkins_csrf_crumb_retriever;

    protected function setUp(): void
    {
        $this->jenkins_csrf_crumb_retriever = Mockery::mock(JenkinsCSRFCrumbRetriever::class);
    }

    public function testLaunchJobBuildThrowsAnExceptionOnFailedRequest(): void
    {
        $http_client = new Client();
        $http_client->addResponse(HTTPFactoryBuilder::responseFactory()->createResponse(500));

        $this->jenkins_csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')->andReturn('');

        $jenkins_client = new Jenkins_Client(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->jenkins_csrf_crumb_retriever
        );

        $this->expectException(Jenkins_ClientUnableToLaunchBuildException::class);
        $jenkins_client->launchJobBuild('https://some.url.example.com/job/my_job');
    }

    public function testLaunchJobBuildThrowsAnExceptionOnNetworkFailure(): void
    {
        $http_client = Mockery::mock(ClientInterface::class);
        $http_client->shouldReceive('sendRequest')->andThrow(
            new class extends Exception implements ClientExceptionInterface {
            }
        );

        $this->jenkins_csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')->andReturn('');

        $jenkins_client = new Jenkins_Client(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->jenkins_csrf_crumb_retriever
        );

        $this->expectException(Jenkins_ClientUnableToLaunchBuildException::class);
        $jenkins_client->launchJobBuild('https://some.url.example.com/job/my_job');
    }

    /**
     * @testWith [200]
     *           [201]
     */
    public function testLaunchJobSetsCorrectOptions(int $http_response_status_code): void
    {
        $http_client    = new Client();
        $jenkins_client = new Jenkins_Client(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->jenkins_csrf_crumb_retriever
        );

        $this->jenkins_csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')->andReturn('');

        $http_client->addResponse(HTTPFactoryBuilder::responseFactory()->createResponse($http_response_status_code));

        $job_url          = 'https://ci.example.com/job/dylanJob/';
        $build_parameters = [
            'my_param' => 'mickey mooouse',
        ];

        $jenkins_client->launchJobBuild($job_url, $build_parameters);

        $requests = $http_client->getRequests();
        $this->assertCount(1, $requests);
        $request = $requests[0];
        $this->assertEquals('POST', $request->getMethod());
        $expected_body = 'json={"parameter":[{"name":"my_param","value":"mickey mooouse"}]}';
        $this->assertEquals($expected_body, $request->getBody()->getContents());
    }

    public function testTokenAsParameter(): void
    {
        $http_client = new Client();

        $jenkins_client = new Jenkins_Client(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->jenkins_csrf_crumb_retriever
        );

        $this->jenkins_csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')->andReturn('');

        $job_url = 'https://ci.example.com/job/dylanJob';

        $jenkins_client->setToken('thou shall not pass');
        $jenkins_client->launchJobBuild($job_url);

        $requests = $http_client->getRequests();
        $this->assertCount(1, $requests);
        $request = $requests[0];
        $this->assertEquals('token=thou+shall+not+pass', $request->getUri()->getQuery());
    }

    public function testLaunchJobWithParametersGivenByUser(): void
    {
        $http_client = new Client();

        $this->jenkins_csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')->andReturn('');

        $jenkins_client = new Jenkins_Client(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->jenkins_csrf_crumb_retriever
        );

        $job_url = 'https://ci.example.com/job/dylanJob/buildWithParameters?stuff=bla';

        $jenkins_client->launchJobBuild($job_url);

        $requests = $http_client->getRequests();
        $this->assertCount(1, $requests);
        $request = $requests[0];
        $this->assertEquals($job_url, (string) $request->getUri());
    }

    public function testLaunchJobWithParametersGivenByUserAndToken(): void
    {
        $http_client = new Client();

        $this->jenkins_csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')->andReturn('');

        $jenkins_client = new Jenkins_Client(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->jenkins_csrf_crumb_retriever
        );

        $job_url = 'https://ci.example.com/job/dylanJob/buildWithParameters?stuff=bla';

        $jenkins_client->setToken('thou shall not pass');
        $jenkins_client->launchJobBuild($job_url);

        $requests = $http_client->getRequests();
        $this->assertCount(1, $requests);
        $request = $requests[0];
        $this->assertEquals($job_url . '&token=thou+shall+not+pass', (string) $request->getUri());
    }

    public function testLaunchJobWithACSRFCrumbHeader(): void
    {
        $http_client = new Client();

        $csrf_crumb_header_name  = 'CsrfCrumb';
        $csrf_crumb_header_value = 'aaaaaaaaaa';
        $this->jenkins_csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')
            ->andReturn($csrf_crumb_header_name . ':' . $csrf_crumb_header_value);

        $jenkins_client = new Jenkins_Client(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->jenkins_csrf_crumb_retriever
        );

        $job_url = 'https://ci.example.com/job/myjob';

        $jenkins_client->launchJobBuild($job_url);

        $requests = $http_client->getRequests();
        $this->assertCount(1, $requests);
        $request = $requests[0];
        $this->assertEquals($csrf_crumb_header_value, $request->getHeaderLine($csrf_crumb_header_name));
    }

    public function testLaunchJobBuildWithInvalidURL(): void
    {
        $http_client = new Client();

        $jenkins_client = new Jenkins_Client(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->jenkins_csrf_crumb_retriever
        );

        $this->expectException(Jenkins_ClientUnableToLaunchBuildException::class);
        $jenkins_client->launchJobBuild('https://some.url.example.com/not_a_job_url');
    }
}
