<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\REST\v1;

use GitPermissionsManager;
use Luracast\Restler\RestException;
use Project;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\REST\I18NRestException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class WebhookSecretGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitPermissionsManager
     */
    private $permissions_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&WebhookCreator
     */
    private $webhook_creator;

    private WebhookSecretGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository_integration_factory = $this->createMock(GitlabRepositoryIntegrationFactory::class);
        $this->permissions_manager            = $this->createMock(GitPermissionsManager::class);
        $this->credentials_retriever          = $this->createMock(CredentialsRetriever::class);
        $this->webhook_creator                = $this->createMock(WebhookCreator::class);

        $this->generator = new WebhookSecretGenerator(
            $this->repository_integration_factory,
            $this->permissions_manager,
            $this->credentials_retriever,
            $this->webhook_creator,
        );
    }

    public function test404IfRequestedRepositoryIsNotFound(): void
    {
        $id = 123;

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->generator->regenerate($id, $this->createMock(\PFUser::class));
    }

    public function test403IfUserIsNotGitAdminOfTheProjectWhereTheGitlabRepositoryIsIntegrated(): void
    {
        $user = $this->createMock(\PFUser::class);

        $id = 123;

        $repository = $this->createMock(GitlabRepositoryIntegration::class);

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project)
            ->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->generator->regenerate($id, $user);
    }

    public function test400IfNoCredentialsAreFoundForTheRepository(): void
    {
        $user = $this->createMock(\PFUser::class);

        $id = 123;

        $repository = $this->createMock(GitlabRepositoryIntegration::class);
        $repository->method('getGitlabRepositoryId')->willReturn(123);
        $repository->method('getGitlabServerUrl')->willReturn('https://gitlab.example.com');

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project)
            ->willReturn(true);

        $this->credentials_retriever
            ->method('getCredentials')
            ->with($repository)
            ->willReturn(null);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->generator->regenerate($id, $user);
    }

    public function test400IfGitlabServerDoesNotAcceptsTheWebhook(): void
    {
        $user = $this->createMock(\PFUser::class);

        $id = 123;

        $repository = $this->createMock(GitlabRepositoryIntegration::class);
        $repository->method('getGitlabRepositoryId')->willReturn(123);
        $repository->method('getGitlabServerUrl')->willReturn('https://gitlab.example.com');

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project)
            ->willReturn(true);

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->method('getCredentials')
            ->with($repository)
            ->willReturn($credentials);

        $this->webhook_creator
            ->method('generateWebhookInGitlabProject')
            ->with($credentials, $repository)
            ->willThrowException(new GitlabRequestException(400, 'getGitlabServerMessage'));

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->generator->regenerate($id, $user);
    }

    public function test500IfWeAreNotAbleToParseGitlabResponse(): void
    {
        $user = $this->createMock(\PFUser::class);

        $id = 123;

        $repository = $this->createMock(GitlabRepositoryIntegration::class);
        $repository->method('getGitlabRepositoryId')->willReturn(123);
        $repository->method('getGitlabServerUrl')->willReturn('https://gitlab.example.com');

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project)
            ->willReturn(true);

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->method('getCredentials')
            ->with($repository)
            ->willReturn($credentials);

        $this->webhook_creator
            ->method('generateWebhookInGitlabProject')
            ->with($credentials, $repository)
            ->willThrowException(new GitlabResponseAPIException('error'));


        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(500);

        $this->generator->regenerate($id, $user);
    }

    public function testItSavesTheNewTokenIfGitlabServerAcceptsTheNewToken(): void
    {
        $user = $this->createMock(\PFUser::class);

        $id = 123;

        $repository = $this->createMock(GitlabRepositoryIntegration::class);
        $repository->method('getGitlabRepositoryId')->willReturn(123);
        $repository->method('getGitlabServerUrl')->willReturn('https://gitlab.example.com');

        $this->repository_integration_factory
            ->method('getIntegrationById')
            ->with(123)
            ->willReturn($repository);

        $project = $this->createMock(Project::class);
        $repository->method('getProject')->willReturn($project);

        $this->permissions_manager
            ->method('userIsGitAdmin')
            ->with($user, $project)
            ->willReturn(true);

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->method('getCredentials')
            ->with($repository)
            ->willReturn($credentials);

        $this->webhook_creator
            ->expects($this->once())
            ->method('generateWebhookInGitlabProject')
            ->with($credentials, $repository);

        $this->generator->regenerate($id, $user);
    }
}
