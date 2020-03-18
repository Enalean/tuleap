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

namespace Tuleap\OAuth2Server\App;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\Builders\UserTestBuilder;

final class AppFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AppFactory
     */
    private $app_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppDao
     */
    private $app_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\ProjectManager
     */
    private $project_manager;

    protected function setUp(): void
    {
        $this->app_dao         = M::mock(AppDao::class);
        $this->project_manager = M::mock(\ProjectManager::class);
        $this->app_factory     = new AppFactory($this->app_dao, $this->project_manager);
    }

    public function testGetAppsForProject(): void
    {
        $rows    = [
            ['id' => 1, 'name' => 'Jenkins', 'redirect_endpoint' => 'https://jenkins.example.com', 'use_pkce' => 1],
            ['id' => 2, 'name' => 'My custom REST client', 'redirect_endpoint' => 'https://my-custom-client.example.com', 'use_pkce' => 0]
        ];
        $project = M::mock(\Project::class);
        $this->app_dao->shouldReceive('searchByProject')
            ->once()
            ->with($project)
            ->andReturn($rows);

        $result = $this->app_factory->getAppsForProject($project);
        $this->assertEquals(
            [
                new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, $project),
                new OAuth2App(2, 'My custom REST client', 'https://my-custom-client.example.com', false, $project)
            ],
            $result
        );
    }

    public function testGetAppMatchingClientIdThrowsWhenIDNotFoundInDatabase(): void
    {
        $this->app_dao->shouldReceive('searchByClientId')
            ->once()
            ->andReturnNull();
        $client_id = ClientIdentifier::fromClientId('tlp-client-id-1');

        $this->expectException(OAuth2AppNotFoundException::class);
        $this->app_factory->getAppMatchingClientId($client_id);
    }

    public function testGetAppMatchingClientIdThrowsWhenProjectNotFound(): void
    {
        $this->app_dao->shouldReceive('searchByClientId')
            ->once()
            ->andReturn(
                ['id' => 1, 'name' => 'Jenkins', 'project_id' => 404, 'redirect_endpoint' => 'https://jenkins.example.com']
            );
        $client_id = ClientIdentifier::fromClientId('tlp-client-id-1');
        $this->project_manager->shouldReceive('getValidProject')
            ->once()
            ->with(404)
            ->andThrow(new \Project_NotFoundException());

        $this->expectException(OAuth2AppNotFoundException::class);
        $this->app_factory->getAppMatchingClientId($client_id);
    }

    public function testGetAppMatchingClientIdReturnsAnApp(): void
    {
        $this->app_dao->shouldReceive('searchByClientId')
            ->once()
            ->andReturn(
                ['id' => 1, 'name' => 'Jenkins', 'project_id' => 102, 'redirect_endpoint' => 'https://jenkins.example.com', 'use_pkce' => 1]
            );
        $client_id = ClientIdentifier::fromClientId('tlp-client-id-1');
        $project   = M::mock(\Project::class);
        $this->project_manager->shouldReceive('getValidProject')
            ->once()
            ->with(102)
            ->andReturn($project);

        $result = $this->app_factory->getAppMatchingClientId($client_id);
        $this->assertEquals(new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, $project), $result);
    }

    public function testGetAppsAuthorizedByUserReturnsApps(): void
    {
        $rows = [
            [
                'id'                => 1,
                'name'              => 'Jenkins',
                'redirect_endpoint' => 'https://jenkins.example.com',
                'project_id'        => 204,
                'use_pkce'          => 1
            ], [
                'id'                => 2,
                'name'              => 'My custom REST client',
                'redirect_endpoint' => 'https://my-custom-client.example.com',
                'project_id'        => 205,
                'use_pkce'          => 0
            ]
        ];
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->app_dao->shouldReceive('searchAuthorizedAppsByUser')
            ->once()
            ->with($user)
            ->andReturn($rows);
        $project_204 = new \Project(['group_id' => 204]);
        $project_205 = new \Project(['group_id' => 205]);
        $this->project_manager->shouldReceive('getValidProject')->once()->with(204)->andReturn($project_204);
        $this->project_manager->shouldReceive('getValidProject')->once()->with(205)->andReturn($project_205);

        $result = $this->app_factory->getAppsAuthorizedByUser($user);
        $this->assertEquals(
            [
                new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, $project_204),
                new OAuth2App(2, 'My custom REST client', 'https://my-custom-client.example.com', false, $project_205)
            ],
            $result
        );
    }

    public function testGetAppsAuthorizedByUserSkipsAppsWithProjectsNotFound(): void
    {
        $rows = [
            [
                'id'                => 1,
                'name'              => 'Jenkins',
                'redirect_endpoint' => 'https://jenkins.example.com',
                'project_id'        => 204,
                'use_pkce'          => 1
            ], [
                'id'                => 4,
                'name'              => 'Project is invalid',
                'redirect_endpoint' => 'https://example.com',
                'project_id'        => 404,
                'use_pkce'          => 1
            ]
        ];
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->app_dao->shouldReceive('searchAuthorizedAppsByUser')
            ->once()
            ->with($user)
            ->andReturn($rows);
        $project_204 = new \Project(['group_id' => 204]);
        $this->project_manager->shouldReceive('getValidProject')->once()->with(204)->andReturn($project_204);
        $this->project_manager->shouldReceive('getValidProject')
            ->once()
            ->with(404)
            ->andThrow(\Project_NotFoundException::class);

        $result = $this->app_factory->getAppsAuthorizedByUser($user);
        $this->assertEquals([
            new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, $project_204),
        ], $result);
    }
}
