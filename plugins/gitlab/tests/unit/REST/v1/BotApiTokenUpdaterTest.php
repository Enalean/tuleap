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
use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenInserter;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\REST\I18NRestException;

class BotApiTokenUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabProjectBuilder
     */
    private $project_builder;
    /**
     * @var GitPermissionsManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $permissions_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabBotApiTokenInserter
     */
    private $bot_api_token_inserter;
    /**
     * @var BotApiTokenUpdater
     */
    private $updater;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WebhookCreator
     */
    private $webhook_creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        $this->repository_factory     = Mockery::mock(GitlabRepositoryFactory::class);
        $this->project_builder        = Mockery::mock(GitlabProjectBuilder::class);
        $this->permissions_manager    = Mockery::mock(GitPermissionsManager::class);
        $this->bot_api_token_inserter = Mockery::mock(GitlabBotApiTokenInserter::class);
        $this->webhook_creator        = Mockery::mock(WebhookCreator::class);
        $this->logger                 = Mockery::mock(LoggerInterface::class);

        $this->updater = new BotApiTokenUpdater(
            $this->repository_factory,
            $this->project_builder,
            $this->permissions_manager,
            $this->bot_api_token_inserter,
            $this->webhook_creator,
            $this->logger,
        );
    }

    public function test404IfRequestedRepositoryIsNotFound(): void
    {
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            new ConcealedString('My New Token'),
        );

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryById')
            ->with(123)
            ->andReturnNull();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->updater->update($patch, Mockery::mock(\PFUser::class));
    }

    public function test404IfUserIsNotGitAdminOfTheProjectWhereTheGitlabRepositoryIsIntegrated(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            new ConcealedString('My New Token'),
        );

        $repository = Mockery::mock(GitlabRepositoryIntegration::class);

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryById')
            ->with(123)
            ->andReturn($repository);

        $project_a = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project_a);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_a)
            ->andReturnFalse();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->updater->update($patch, $user);
    }

    public function test400IfGitlabServerDoesNotAcceptsTheNewToken(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $token = new ConcealedString('My New Token');
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            $token,
        );

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

        $project_a = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project_a);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_a)
            ->andReturnTrue();

        $this->project_builder
            ->shouldReceive('getProjectFromGitlabAPI')
            ->with(
                Mockery::on(
                    function (Credentials $credentials) use ($token) {
                        return $credentials->getBotApiToken()->getToken()->isIdenticalTo($token)
                            && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
                    }
                ),
                123
            )
            ->andThrow(Mockery::spy(GitlabRequestException::class));

        $this->logger->shouldReceive('error')->once();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->updater->update($patch, $user);
    }

    public function test500IfGitlabServerAcceptsTheNewTokenButReturnsGibberish(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $token = new ConcealedString('My New Token');
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            $token,
        );

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

        $project_a = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project_a);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_a)
            ->andReturnTrue();

        $this->project_builder
            ->shouldReceive('getProjectFromGitlabAPI')
            ->with(
                Mockery::on(
                    function (Credentials $credentials) use ($token) {
                        return $credentials->getBotApiToken()->getToken()->isIdenticalTo($token)
                            && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
                    }
                ),
                123
            )
            ->andThrow(Mockery::spy(GitlabResponseAPIException::class));

        $this->logger->shouldReceive('error')->once();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(500);

        $this->updater->update($patch, $user);
    }

    public function test400IfGitlabServerAcceptsTheNewTokenButNotTheCreationOfTheWebhook(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $token = new ConcealedString('My New Token');
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            $token,
        );

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

        $project_a = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project_a);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_a)
            ->andReturnTrue();

        $expected_credentials = Mockery::on(
            function (Credentials $credentials) use ($token) {
                return $credentials->getBotApiToken()->getToken()->isIdenticalTo($token)
                    && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
            }
        );

        $this->project_builder
            ->shouldReceive('getProjectFromGitlabAPI')
            ->with($expected_credentials, 123);

        $this->webhook_creator
            ->shouldReceive('generateWebhookInGitlabProject')
            ->with($expected_credentials, $repository)
            ->once()
            ->andThrow(Mockery::spy(GitlabRequestException::class));

        $this->bot_api_token_inserter
            ->shouldReceive('insertToken')
            ->with($repository, $token)
            ->never();

        $this->logger->shouldReceive('error')->once();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->updater->update($patch, $user);
    }

    public function testItSavesTheNewTokenIfGitlabServerAcceptsTheNewTokenAndTheCreationOfTheWebhook(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $token = new ConcealedString('My New Token');
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            $token,
        );

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

        $project_a = Mockery::mock(Project::class);
        $repository->shouldReceive('getProject')->andReturn($project_a);

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_a)
            ->andReturnTrue();

        $expected_credentials = Mockery::on(
            function (Credentials $credentials) use ($token) {
                return $credentials->getBotApiToken()->getToken()->isIdenticalTo($token)
                    && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
            }
        );

        $this->project_builder
            ->shouldReceive('getProjectFromGitlabAPI')
            ->with($expected_credentials, 123);

        $this->webhook_creator
            ->shouldReceive('generateWebhookInGitlabProject')
            ->with($expected_credentials, $repository)
            ->once();

        $this->bot_api_token_inserter
            ->shouldReceive('insertToken')
            ->with($repository, $token)
            ->once();

        $this->updater->update($patch, $user);
    }
}
