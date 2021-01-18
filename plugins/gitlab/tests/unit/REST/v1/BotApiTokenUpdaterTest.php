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
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenInserter;
use Tuleap\REST\I18NRestException;

class BotApiTokenUpdaterTest extends TestCase
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryProjectRetriever
     */
    private $project_retriever;
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

    protected function setUp(): void
    {
        $this->repository_factory     = Mockery::mock(GitlabRepositoryFactory::class);
        $this->project_builder        = Mockery::mock(GitlabProjectBuilder::class);
        $this->project_retriever      = Mockery::mock(GitlabRepositoryProjectRetriever::class);
        $this->permissions_manager    = Mockery::mock(GitPermissionsManager::class);
        $this->bot_api_token_inserter = Mockery::mock(GitlabBotApiTokenInserter::class);

        $this->updater = new BotApiTokenUpdater(
            $this->repository_factory,
            $this->project_builder,
            $this->project_retriever,
            $this->permissions_manager,
            $this->bot_api_token_inserter,
        );
    }

    public function test404IfRequestedRepositoryIsNotFound(): void
    {
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            'https://gitlab.example.com/repo/full_url',
            new ConcealedString('My New Token'),
        );

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->with(123, "https://gitlab.example.com/repo/full_url")
            ->andReturnNull();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->updater->update($patch, Mockery::mock(\PFUser::class));
    }

    public function test404IfUserIsNotGitAdminOfAtLeastOneProjectWhereTheGitlabRepositoryIsIntegrated(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            'https://gitlab.example.com/repo/full_url',
            new ConcealedString('My New Token'),
        );

        $repository = Mockery::mock(GitlabRepository::class);

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->with(123, "https://gitlab.example.com/repo/full_url")
            ->andReturn($repository);

        $project_a = Mockery::mock(Project::class);
        $project_b = Mockery::mock(Project::class);

        $this->project_retriever
            ->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->with($repository)
            ->andReturn(
                [
                    $project_a,
                    $project_b,
                ]
            );

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_a)
            ->andReturnFalse();

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_b)
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
            'https://gitlab.example.com/repo/full_url',
            $token,
        );

        $repository = Mockery::mock(
            GitlabRepository::class,
            [
                'getGitlabRepositoryId' => 123,
                'getGitlabServerUrl'    => 'https://gitlab.example.com',
            ]
        );

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->with(123, "https://gitlab.example.com/repo/full_url")
            ->andReturn($repository);

        $project_a = Mockery::mock(Project::class);
        $project_b = Mockery::mock(Project::class);

        $this->project_retriever
            ->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->with($repository)
            ->andReturn(
                [
                    $project_a,
                    $project_b,
                ]
            );

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_a)
            ->andReturnFalse();

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_b)
            ->andReturnTrue();

        $this->project_builder
            ->shouldReceive('getProjectFromGitlabAPI')
            ->with(
                Mockery::on(
                    function (Credentials $credentials) use ($token) {
                        return $credentials->getBotApiToken()->isIdenticalTo($token)
                            && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
                    }
                ),
                123
            )
            ->andThrow(Mockery::spy(GitlabRequestException::class));

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
            'https://gitlab.example.com/repo/full_url',
            $token,
        );

        $repository = Mockery::mock(
            GitlabRepository::class,
            [
                'getGitlabRepositoryId' => 123,
                'getGitlabServerUrl'    => 'https://gitlab.example.com',
            ]
        );

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->with(123, "https://gitlab.example.com/repo/full_url")
            ->andReturn($repository);

        $project_a = Mockery::mock(Project::class);
        $project_b = Mockery::mock(Project::class);

        $this->project_retriever
            ->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->with($repository)
            ->andReturn(
                [
                    $project_a,
                    $project_b,
                ]
            );

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_a)
            ->andReturnFalse();

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_b)
            ->andReturnTrue();

        $this->project_builder
            ->shouldReceive('getProjectFromGitlabAPI')
            ->with(
                Mockery::on(
                    function (Credentials $credentials) use ($token) {
                        return $credentials->getBotApiToken()->isIdenticalTo($token)
                            && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
                    }
                ),
                123
            )
            ->andThrow(Mockery::spy(GitlabResponseAPIException::class));

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(500);

        $this->updater->update($patch, $user);
    }

    public function testItSavesTheNewTokenIfGitlabServerAcceptsTheNewToken(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $token = new ConcealedString('My New Token');
        $patch = new ConcealedBotApiTokenPatchRepresentation(
            123,
            'https://gitlab.example.com/repo/full_url',
            $token,
        );

        $repository = Mockery::mock(
            GitlabRepository::class,
            [
                'getGitlabRepositoryId' => 123,
                'getGitlabServerUrl'    => 'https://gitlab.example.com',
            ]
        );

        $this->repository_factory
            ->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->with(123, "https://gitlab.example.com/repo/full_url")
            ->andReturn($repository);

        $project_a = Mockery::mock(Project::class);
        $project_b = Mockery::mock(Project::class);

        $this->project_retriever
            ->shouldReceive('getProjectsGitlabRepositoryIsIntegratedIn')
            ->with($repository)
            ->andReturn(
                [
                    $project_a,
                    $project_b,
                ]
            );

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_a)
            ->andReturnFalse();

        $this->permissions_manager
            ->shouldReceive('userIsGitAdmin')
            ->with($user, $project_b)
            ->andReturnTrue();

        $this->project_builder
            ->shouldReceive('getProjectFromGitlabAPI')
            ->with(
                Mockery::on(
                    function (Credentials $credentials) use ($token) {
                        return $credentials->getBotApiToken()->isIdenticalTo($token)
                            && $credentials->getGitlabServerUrl() === 'https://gitlab.example.com';
                    }
                ),
                123
            );

        $this->bot_api_token_inserter
            ->shouldReceive('insertToken')
            ->with($repository, $token)
            ->once();

        $this->updater->update($patch, $user);
    }
}
