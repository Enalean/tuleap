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

namespace Tuleap\HudsonGit\Hook;

use DateTimeImmutable;
use GitRepository;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerFactory;
use Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookResponse;
use Tuleap\HudsonGit\Log\LogCreator;
use Tuleap\HudsonGit\PollingResponse;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class HookTriggerControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private HookTriggerController $controller;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&HookDao
     */
    private $dao;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&JenkinsClient
     */
    private $jenkins_client;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&LogCreator
     */
    private $log_creator;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject&JenkinsServerFactory
     */
    private $jenkins_server_factory;

    /**
     * @var GitRepository&PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                    = $this->createMock(HookDao::class);
        $this->jenkins_client         = $this->createMock(JenkinsClient::class);
        $this->logger                 = $this->createMock(LoggerInterface::class);
        $this->log_creator            = $this->createMock(LogCreator::class);
        $this->jenkins_server_factory = $this->createMock(JenkinsServerFactory::class);

        $this->controller = new HookTriggerController(
            $this->dao,
            $this->jenkins_client,
            $this->logger,
            $this->log_creator,
            $this->jenkins_server_factory
        );

        $this->repository = $this->createMock(GitRepository::class);

        $this->project = ProjectTestBuilder::aProject()->build();
        $this->repository->method('getProject')->willReturn($this->project);
        $this->repository->method('getId')->willReturn(1);

        $this->repository->method('getAccessURL')->willReturn([
            'http' => 'https://example.com/repo01',
            'ssh'  => 'example.com/repo01',
        ]);
    }

    public function testItTriggersRepositoryHooks(): void
    {
        $this->dao->expects(self::once())->method('searchById')->with(1)->willReturn(
            ['jenkins_server_url' => 'https://example.com/jenkins', 'encrypted_token' => 'token', 'is_commit_reference_needed' => true],
        );

        $polling_response = $this->createMock(PollingResponse::class);
        $polling_response->method('getJobPaths')->willReturn([
            'https://example.com/jenkins/job01',
        ]);
        $polling_response->method('getBody')->willReturn('Response body');
        $this->jenkins_client->expects(self::exactly(2))
            ->method('pushGitNotifications')
            ->with('https://example.com/jenkins', self::anything(), 'token', 'da39a3ee5e6b4b0d3255bfef95601890afd80709')
            ->willReturn($polling_response);

        $hook_response = new JenkinsTuleapPluginHookResponse(
            200,
            ''
        );
        $this->jenkins_client->expects(self::once())->method('pushJenkinsTuleapPluginNotification')->willReturn($hook_response);


        $this->log_creator->expects(self::once())->method('createForRepository');
        $this->log_creator->expects(self::never())->method('createForProject');

        $this->logger->method('debug');
        $this->logger->expects(self::never())->method('error');

        $this->jenkins_server_factory->expects(self::once())->method('getJenkinsServerOfProject')->willReturn([]);

        $date_time = new DateTimeImmutable();

        $this->controller->trigger(
            $this->repository,
            'da39a3ee5e6b4b0d3255bfef95601890afd80709',
            $date_time
        );
    }

    public function testItTriggersRepositoryHooksWithoutCommitReference(): void
    {
        $this->dao->expects(self::once())->method('searchById')->with(1)->willReturn(
            ['jenkins_server_url' => 'https://example.com/jenkins', 'encrypted_token' => 'token', 'is_commit_reference_needed' => false],
        );

        $polling_response = $this->createMock(PollingResponse::class);
        $polling_response->method('getJobPaths')->willReturn([
            'https://example.com/jenkins/job01',
        ]);
        $polling_response->method('getBody')->willReturn('Response body');
        $this->jenkins_client->expects(self::exactly(2))
            ->method('pushGitNotifications')
            ->with('https://example.com/jenkins', self::anything(), 'token', null)
            ->willReturn($polling_response);

        $hook_response = new JenkinsTuleapPluginHookResponse(
            200,
            ''
        );
        $this->jenkins_client->expects(self::once())->method('pushJenkinsTuleapPluginNotification')->willReturn($hook_response);

        $this->log_creator->expects(self::once())->method('createForRepository');
        $this->log_creator->expects(self::never())->method('createForProject');

        $this->logger->method('debug');
        $this->logger->expects(self::never())->method('error');

        $this->jenkins_server_factory->expects(self::once())->method('getJenkinsServerOfProject')->willReturn([]);

        $date_time = new DateTimeImmutable();

        $this->controller->trigger(
            $this->repository,
            'da39a3ee5e6b4b0d3255bfef95601890afd80709',
            $date_time
        );
    }

    public function testItTriggersEachTransportsInRepositoryHooks(): void
    {
        $this->dao->expects(self::once())->method('searchById')->with(1)->willReturn(
            ['jenkins_server_url' => 'https://example.com/jenkins', 'encrypted_token' => null, 'is_commit_reference_needed' => true],
        );

        $polling_response = $this->createMock(PollingResponse::class);
        $polling_response->method('getJobPaths')->willReturn([
            'https://example.com/jenkins/job01',
        ]);
        $polling_response->method('getBody')->willReturn('Response body');

        $this->jenkins_client
            ->method('pushGitNotifications')
            ->willReturnCallback(
                function (string $server_url, string $repository_url) use ($polling_response): PollingResponse {
                    if ($repository_url === "https://example.com/repo01") {
                        throw new UnableToLaunchBuildException();
                    } elseif ($repository_url === "example.com/repo01") {
                        return $polling_response;
                    } else {
                        self::fail("Not expected");
                    }
                }
            );

        $hook_response = new JenkinsTuleapPluginHookResponse(
            200,
            ''
        );
        $this->jenkins_client->expects(self::once())->method('pushJenkinsTuleapPluginNotification')->willReturn($hook_response);

        $this->log_creator->expects(self::once())->method('createForRepository');
        $this->log_creator->expects(self::never())->method('createForProject');

        $this->logger->method('debug');
        $this->logger->expects(self::once())->method('error');

        $this->jenkins_server_factory->expects(self::once())->method('getJenkinsServerOfProject')->willReturn([]);

        $date_time = new DateTimeImmutable();

        $this->controller->trigger(
            $this->repository,
            'da39a3ee5e6b4b0d3255bfef95601890afd80709',
            $date_time
        );
    }

    public function testItTriggersProjectHooks(): void
    {
        $this->dao->expects(self::once())->method('searchById')->with(1)->willReturn(null);

        $jenkins_server = new JenkinsServer(0, 'https://example.com/jenkins', null, $this->project);
        $this->jenkins_server_factory->expects(self::once())->method('getJenkinsServerOfProject')->willReturn([
            $jenkins_server,
        ]);

        $polling_response = $this->createMock(PollingResponse::class);
        $polling_response->method('getJobPaths')->willReturn([
            'https://example.com/jenkins/job01',
        ]);
        $polling_response->method('getBody')->willReturn('Response body');
        $this->jenkins_client->expects(self::exactly(2))->method('pushGitNotifications')->willReturn($polling_response);

        $hook_response = new JenkinsTuleapPluginHookResponse(
            200,
            ''
        );
        $this->jenkins_client->expects(self::once())->method('pushJenkinsTuleapPluginNotification')->willReturn($hook_response);

        $this->log_creator->expects(self::never())->method('createForRepository');
        $this->log_creator->expects(self::once())->method('createForProject');

        $this->logger->method('debug');
        $this->logger->expects(self::never())->method('error');

        $date_time = new DateTimeImmutable();

        $this->controller->trigger(
            $this->repository,
            'da39a3ee5e6b4b0d3255bfef95601890afd80709',
            $date_time
        );
    }

    public function testItTriggersEachTransportsInProjectHooks(): void
    {
        $this->dao->expects(self::once())->method('searchById')->with(1)->willReturn(null);

        $jenkins_server = new JenkinsServer(0, 'https://example.com/jenkins', null, $this->project);
        $this->jenkins_server_factory->expects(self::once())->method('getJenkinsServerOfProject')->willReturn([
            $jenkins_server,
        ]);

        $polling_response = $this->createMock(PollingResponse::class);
        $polling_response->method('getJobPaths')->willReturn([
            'https://example.com/jenkins/job01',
        ]);
        $polling_response->method('getBody')->willReturn('Response body');

        $this->jenkins_client
            ->method('pushGitNotifications')
            ->willReturnCallback(
                function (string $server_url, string $repository_url, ?string $encrypted_token, ?string $commit_reference) use ($polling_response): PollingResponse {
                    if ($repository_url === "https://example.com/repo01") {
                        throw new UnableToLaunchBuildException();
                    } elseif ($repository_url === "example.com/repo01") {
                        return $polling_response;
                    } else {
                        self::fail("Not expected");
                    }
                }
            );

        $hook_response = new JenkinsTuleapPluginHookResponse(
            200,
            ''
        );
        $this->jenkins_client->expects(self::once())->method('pushJenkinsTuleapPluginNotification')->willReturn($hook_response);

        $this->log_creator->expects(self::never())->method('createForRepository');
        $this->log_creator->expects(self::once())->method('createForProject');

        $this->logger->method('debug');
        $this->logger->expects(self::once())->method('error');

        $date_time = new DateTimeImmutable();

        $this->controller->trigger(
            $this->repository,
            'da39a3ee5e6b4b0d3255bfef95601890afd80709',
            $date_time
        );
    }

    public function testItDoesNotTriggerTheProjectHookIfItHasAlreadyBeenTriggeredByRepository(): void
    {
        $this->dao->expects(self::once())->method('searchById')->with(1)->willReturn(
            ['jenkins_server_url' => 'https://example.com/jenkins', 'encrypted_token' => null, 'is_commit_reference_needed' => false],
        );

        $jenkins_server = new JenkinsServer(0, 'https://example.com/jenkins', null, $this->project);
        $this->jenkins_server_factory->expects(self::once())->method('getJenkinsServerOfProject')->willReturn([
            $jenkins_server,
        ]);

        $polling_response = $this->createMock(PollingResponse::class);
        $polling_response->method('getJobPaths')->willReturn([
            'https://example.com/jenkins/job01',
        ]);
        $polling_response->method('getBody')->willReturn('Response body');
        $this->jenkins_client->expects(self::exactly(2))->method('pushGitNotifications')->willReturn($polling_response);

        $hook_response = new JenkinsTuleapPluginHookResponse(
            200,
            ''
        );
        $this->jenkins_client->expects(self::once())->method('pushJenkinsTuleapPluginNotification')->willReturn($hook_response);

        $this->log_creator->expects(self::once())->method('createForRepository');
        $this->log_creator->expects(self::never())->method('createForProject');

        $this->logger->method('debug');

        $date_time = new DateTimeImmutable();

        $this->controller->trigger(
            $this->repository,
            'da39a3ee5e6b4b0d3255bfef95601890afd80709',
            $date_time
        );
    }
}
