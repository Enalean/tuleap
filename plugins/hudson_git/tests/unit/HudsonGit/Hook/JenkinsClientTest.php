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
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookPayload;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;

final class JenkinsClientTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private EncryptionKey $encryption_key;

    protected function setUp(): void
    {
        $this->encryption_key = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
    }

    public function testJenkinsIsNotified(): void
    {
        $http_client          = new Client();
        $csrf_crumb_retriever = $this->createMock(JenkinsCSRFCrumbRetriever::class);

        $request_factory = $this->createMock(RequestFactoryInterface::class);
        $jenkins_client  = new JenkinsClient(
            $http_client,
            $request_factory,
            $csrf_crumb_retriever,
            $this->createMock(JenkinsTuleapPluginHookPayload::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->encryption_key,
        );

        $expected_parameters = [
            'url' => 'https://myinstance.example.com/plugins/git/project/myrepo.git',
            'sha1' => '8b2f3943e997d2faf4a55ed78e695bda64fad421',
            'token' => 'my_secret_token',
        ];
        $expected_url        = 'https://jenkins.example.com/git/notifyCommit?' . http_build_query($expected_parameters);
        $request_factory
            ->method('createRequest')
            ->with('POST', $expected_url)
            ->willReturn($this->createMock(RequestInterface::class));

        $csrf_crumb_retriever->method('getCSRFCrumbHeader')->willReturn('');
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
            SymmetricCrypto::encrypt(new ConcealedString('my_secret_token'), $this->encryption_key),
            '8b2f3943e997d2faf4a55ed78e695bda64fad421'
        );

        self::assertEqualsCanonicalizing($triggered_jobs, $polling_response->getJobPaths());
        self::assertEquals($body_content, $polling_response->getBody());
    }

    public function testJenkinsIsNotifiedWithoutSha1(): void
    {
        $http_client          = new Client();
        $csrf_crumb_retriever = $this->createMock(JenkinsCSRFCrumbRetriever::class);

        $request_factory = $this->createMock(RequestFactoryInterface::class);
        $jenkins_client  = new JenkinsClient(
            $http_client,
            $request_factory,
            $csrf_crumb_retriever,
            $this->createMock(JenkinsTuleapPluginHookPayload::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->encryption_key
        );

        $expected_parameters = [
            'url' => 'https://myinstance.example.com/plugins/git/project/myrepo.git',
        ];
        $expected_url        = 'https://jenkins.example.com/git/notifyCommit?' . http_build_query($expected_parameters);
        $request_factory
            ->method('createRequest')
            ->with('POST', $expected_url)
            ->willReturn($this->createMock(RequestInterface::class));

        $csrf_crumb_retriever->method('getCSRFCrumbHeader')->willReturn('');
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
            null,
            null
        );

        self::assertEqualsCanonicalizing($triggered_jobs, $polling_response->getJobPaths());
        self::assertEquals($body_content, $polling_response->getBody());
    }

    public function testJenkinsTuleapBranchSourcePluginIsNotified(): void
    {
        $http_client          = new Client();
        $csrf_crumb_retriever = $this->createMock(JenkinsCSRFCrumbRetriever::class);

        $payload = $this->createMock(JenkinsTuleapPluginHookPayload::class);

        $payload_content = [
            'tuleapProjectId' => '1',
            'repositoryName'  => 'AMG',
            'branchName'      => 'A35',
        ];

        $payload->method('getPayload')->willReturn($payload_content);

        $stream_factory = $this->createMock(StreamFactoryInterface::class);
        $stream_factory->expects(self::once())->method('createStream')->with(json_encode($payload->getPayload()));

        $jenkins_client = new JenkinsClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            $csrf_crumb_retriever,
            $payload,
            $stream_factory,
            $this->encryption_key,
        );

        $csrf_crumb_retriever->method('getCSRFCrumbHeader')->willReturn('');
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
        $csrf_crumb_retriever = $this->createMock(JenkinsCSRFCrumbRetriever::class);

        $payload = $this->createMock(JenkinsTuleapPluginHookPayload::class);

        $payload_content = [
            'tuleapProjectId' => '1',
            'repositoryName'  => 'AMG',
            'branchName'      => 'A35',
        ];

        $payload->method('getPayload')->willReturn($payload_content);

        $stream_factory = $this->createMock(StreamFactoryInterface::class);
        $stream_factory->expects(self::once())->method('createStream')->with(json_encode($payload->getPayload()));

        $jenkins_client = new JenkinsClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            $csrf_crumb_retriever,
            $payload,
            $stream_factory,
            $this->encryption_key,
        );

        $csrf_crumb_retriever->method('getCSRFCrumbHeader')->willReturn('');
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
