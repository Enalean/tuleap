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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\REST\I18NRestException;

class WebhookSecretGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var GitPermissionsManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $permissions_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WebhookCreator
     */
    private $webhook_creator;
    /**
     * @var WebhookSecretGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->repository_factory    = Mockery::mock(GitlabRepositoryFactory::class);
        $this->permissions_manager   = Mockery::mock(GitPermissionsManager::class);
        $this->credentials_retriever = Mockery::mock(CredentialsRetriever::class);
        $this->webhook_creator       = Mockery::mock(WebhookCreator::class);

        $this->generator = new WebhookSecretGenerator(
            $this->repository_factory,
            $this->permissions_manager,
            $this->credentials_retriever,
            $this->webhook_creator,
        );
    }

    public function test404IfRequestedRepositoryIsNotFound(): void
    {
        $patch = new GitlabRepositoryWebhookSecretPatchRepresentation();

        $patch->gitlab_integration_id = 123;

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryById')
            ->with(123)
            ->andReturnNull();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->generator->regenerate($patch, Mockery::mock(\PFUser::class));
    }

    public function test404IfUserIsNotGitAdminOfTheProjectWhereTheGitlabRepositoryIsIntegrated(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $patch = new GitlabRepositoryWebhookSecretPatchRepresentation();

        $patch->gitlab_integration_id = 123;

        $repository = Mockery::mock(GitlabRepositoryIntegration::class);

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryById')
            ->with(123)
            ->andReturn($repository);

        $project = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project)
            ->andReturnFalse();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->generator->regenerate($patch, $user);
    }

    public function test400IfNoCredentialsAreFoundForTheRepository(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $patch = new GitlabRepositoryWebhookSecretPatchRepresentation();

        $patch->gitlab_integration_id = 123;

        $repository = Mockery::mock(
            GitlabRepositoryIntegration::class,
            [
                'getGitlabRepositoryId' => 123,
                'getGitlabServerUrl'    => 'https://gitlab.example.com',
            ]
        );

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryById')
            ->with(123)
            ->andReturn($repository);

        $project = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project)
            ->andReturnTrue();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($repository)
            ->andReturnNull();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->generator->regenerate($patch, $user);
    }

    public function test400IfGitlabServerDoesNotAcceptsTheWebhook(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $patch = new GitlabRepositoryWebhookSecretPatchRepresentation();

        $patch->gitlab_integration_id = 123;

        $repository = Mockery::mock(
            GitlabRepositoryIntegration::class,
            [
                'getGitlabRepositoryId' => 123,
                'getGitlabServerUrl'    => 'https://gitlab.example.com',
            ]
        );

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryById')
            ->with(123)
            ->andReturn($repository);

        $project = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project)
            ->andReturnTrue();

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($repository)
            ->andReturn($credentials);

        $this->webhook_creator
            ->shouldReceive('generateWebhookInGitlabProject')
            ->with($credentials, $repository)
            ->andThrow(Mockery::mock(GitlabRequestException::class, ['getGitlabServerMessage' => 'Error']));

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->generator->regenerate($patch, $user);
    }

    public function test500IfWeAreNotAbleToParseGitlabResponse(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $patch = new GitlabRepositoryWebhookSecretPatchRepresentation();

        $patch->gitlab_integration_id = 123;

        $repository = Mockery::mock(
            GitlabRepositoryIntegration::class,
            [
                'getGitlabRepositoryId' => 123,
                'getGitlabServerUrl'    => 'https://gitlab.example.com',
            ]
        );

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryById')
            ->with(123)
            ->andReturn($repository);

        $project = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project)
            ->andReturnTrue();

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($repository)
            ->andReturn($credentials);

        $this->webhook_creator
            ->shouldReceive('generateWebhookInGitlabProject')
            ->with($credentials, $repository)
            ->andThrow(Mockery::mock(GitlabResponseAPIException::class));


        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(500);

        $this->generator->regenerate($patch, $user);
    }

    public function testItSavesTheNewTokenIfGitlabServerAcceptsTheNewToken(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $patch = new GitlabRepositoryWebhookSecretPatchRepresentation();

        $patch->gitlab_integration_id = 123;

        $repository = Mockery::mock(
            GitlabRepositoryIntegration::class,
            [
                'getGitlabRepositoryId' => 123,
                'getGitlabServerUrl'    => 'https://gitlab.example.com',
            ]
        );

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryById')
            ->with(123)
            ->andReturn($repository);

        $project = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project)
            ->andReturnTrue();

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($repository)
            ->andReturn($credentials);

        $this->webhook_creator
            ->shouldReceive('generateWebhookInGitlabProject')
            ->with($credentials, $repository)
            ->once();

        $this->generator->regenerate($patch, $user);
    }
}
