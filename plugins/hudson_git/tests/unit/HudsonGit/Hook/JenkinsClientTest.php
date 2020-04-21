<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook;

use Http\Mock\Client;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookPayload;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;

final class JenkinsClientTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testJenkinsIsNotified(): void
    {
        $http_client          = new Client();
        $csrf_crumb_retriever = Mockery::mock(JenkinsCSRFCrumbRetriever::class);

        $jenkins_client = new JenkinsClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            $csrf_crumb_retriever,
            Mockery::mock(JenkinsTuleapPluginHookPayload::class),
            Mockery::mock(StreamFactoryInterface::class)
        );

        $csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')->andReturn('');
        $http_response_factory = HTTPFactoryBuilder::responseFactory();

        $triggered_jobs = ['https://jenkins.example.com/job1', 'https://jenkins.example.com/job2'];
        $body_content   = 'Body test content';

        $http_client->addResponse(
            $http_response_factory->createResponse()
                                  ->withHeader('Triggered', $triggered_jobs)
                                  ->withBody(HTTPFactoryBuilder::streamFactory()->createStream($body_content))
        );

        $polling_response = $jenkins_client->pushGitNotifications(
            'https://jenkins.example.com',
            'https://myinstance.example.com/plugins/git/project/myrepo.git',
            '8b2f3943e997d2faf4a55ed78e695bda64fad421'
        );

        $this->assertEqualsCanonicalizing($triggered_jobs, $polling_response->getJobPaths());
        $this->assertEquals($body_content, $polling_response->getBody());
    }

    public function testJenkinsTuleapBranchSourcePluginIsNotified(): void
    {
        $http_client          = new Client();
        $csrf_crumb_retriever = Mockery::mock(JenkinsCSRFCrumbRetriever::class);

        $payload = Mockery::mock(JenkinsTuleapPluginHookPayload::class);

        $payload_content = [
            'tuleapProjectId' => '1',
            'repositoryName'  => 'AMG',
            'branchName'      => 'A35'
        ];

        $payload->shouldReceive('getPayload')->andReturn($payload_content);

        $stream_factory = Mockery::mock(StreamFactoryInterface::class);
        $stream_factory->shouldReceive('createStream')->with(json_encode($payload->getPayload()))->once();

        $jenkins_client = new JenkinsClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            $csrf_crumb_retriever,
            $payload,
            $stream_factory
        );

        $csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')->andReturn('');
        $http_response_factory = HTTPFactoryBuilder::responseFactory();

        $http_client->addResponse(
            $http_response_factory->createResponse()
                                  ->withStatus(200)
        );

        $jenkins_client->pushJenkinsTuleapPluginNotification(
            'https://jenkins.example.com'
        );
    }

    public function testThrowsExceptionWhenTheBuildCannotBeTriggered(): void
    {
        $http_client          = new Client();
        $csrf_crumb_retriever = Mockery::mock(JenkinsCSRFCrumbRetriever::class);

        $payload = Mockery::mock(JenkinsTuleapPluginHookPayload::class);

        $payload_content = [
            'tuleapProjectId' => '1',
            'repositoryName'  => 'AMG',
            'branchName'      => 'A35'
        ];

        $payload->shouldReceive('getPayload')->andReturn($payload_content);

        $stream_factory = Mockery::mock(StreamFactoryInterface::class);
        $stream_factory->shouldReceive('createStream')->with(json_encode($payload->getPayload()))->once();

        $jenkins_client = new JenkinsClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            $csrf_crumb_retriever,
            $payload,
            $stream_factory
        );

        $csrf_crumb_retriever->shouldReceive('getCSRFCrumbHeader')->andReturn('');
        $http_response_factory = HTTPFactoryBuilder::responseFactory();

        $http_client->addResponse(
            $http_response_factory->createResponse()
                                  ->withStatus(400)
        );

        $this->expectException(UnableToLaunchBuildException::class);
        $jenkins_client->pushJenkinsTuleapPluginNotification(
            'https://jenkins.example.com'
        );
    }
}
