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

use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AppFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var AppFactory
     */
    private $app_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppDao
     */
    private $app_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\ProjectManager
     */
    private $project_manager;

    protected function setUp(): void
    {
        $this->app_dao         = $this->createMock(AppDao::class);
        $this->project_manager = $this->createMock(\ProjectManager::class);
        $this->app_factory     = new AppFactory($this->app_dao, $this->project_manager);
    }

    public function testGetAppsForProject(): void
    {
        $rows    = [
            ['id' => 1, 'name' => 'Jenkins', 'redirect_endpoint' => 'https://jenkins.example.com', 'use_pkce' => 1],
            ['id' => 2, 'name' => 'My custom REST client', 'redirect_endpoint' => 'https://my-custom-client.example.com', 'use_pkce' => 0],
        ];
        $project = ProjectTestBuilder::aProject()->build();
        $this->app_dao->expects($this->once())->method('searchByProject')
            ->with($project)
            ->willReturn($rows);

        $result = $this->app_factory->getAppsForProject($project);
        $this->assertEquals(
            [
                new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, $project),
                new OAuth2App(2, 'My custom REST client', 'https://my-custom-client.example.com', false, $project),
            ],
            $result
        );
    }

    public function testGetSiteLevelApps(): void
    {
        $rows = [
            ['id' => 3, 'name' => 'Jenkins', 'redirect_endpoint' => 'https://jenkins.example.com', 'use_pkce' => 1],
            ['id' => 4, 'name' => 'My custom REST client', 'redirect_endpoint' => 'https://my-custom-client.example.com', 'use_pkce' => 0],
        ];
        $this->app_dao->expects($this->once())->method('searchSiteLevelApps')->willReturn($rows);

        $result = $this->app_factory->getSiteLevelApps();
        $this->assertEquals(
            [
                new OAuth2App(3, 'Jenkins', 'https://jenkins.example.com', true, null),
                new OAuth2App(4, 'My custom REST client', 'https://my-custom-client.example.com', false, null),
            ],
            $result
        );
    }

    public function testGetAppsAuthorizedByUserReturnsApps(): void
    {
        $rows = [
            [
                'id'                => 1,
                'name'              => 'Jenkins',
                'redirect_endpoint' => 'https://jenkins.example.com',
                'project_id'        => 204,
                'use_pkce'          => 1,
            ],
            [
                'id'                => 2,
                'name'              => 'My custom REST client',
                'redirect_endpoint' => 'https://my-custom-client.example.com',
                'project_id'        => 205,
                'use_pkce'          => 0,
            ],
            [
                'id'                => 3,
                'name'              => 'A site level OAuth2 app',
                'redirect_endpoint' => 'https://site-level-app.example.com',
                'project_id'        => null,
                'use_pkce'          => 1,
            ],
        ];
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->app_dao->expects($this->once())->method('searchAuthorizedAppsByUser')
            ->with($user)
            ->willReturn($rows);
        $project_204 = new \Project(['group_id' => 204]);
        $project_205 = new \Project(['group_id' => 205]);
        $this->project_manager->expects(self::exactly(2))->method('getValidProject')
            ->willReturnCallback(
                fn (int $project_id): \Project => match ($project_id) {
                    204 => $project_204,
                    205 => $project_205,
                }
            );

        $result = $this->app_factory->getAppsAuthorizedByUser($user);
        $this->assertEquals(
            [
                new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, $project_204),
                new OAuth2App(2, 'My custom REST client', 'https://my-custom-client.example.com', false, $project_205),
                new OAuth2App(3, 'A site level OAuth2 app', 'https://site-level-app.example.com', true, null),
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
                'use_pkce'          => 1,
            ], [
                'id'                => 4,
                'name'              => 'Project is invalid',
                'redirect_endpoint' => 'https://example.com',
                'project_id'        => 404,
                'use_pkce'          => 1,
            ],
        ];
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->app_dao->expects($this->once())->method('searchAuthorizedAppsByUser')
            ->with($user)
            ->willReturn($rows);
        $project_204 = new \Project(['group_id' => 204]);
        $this->project_manager->expects(self::exactly(2))->method('getValidProject')->willReturnCallback(
            static function (int $project_id) use ($project_204): \Project {
                if ($project_id === 204) {
                    return $project_204;
                }
                if ($project_id === 404) {
                    throw new \Project_NotFoundException();
                }

                throw new \LogicException(sprintf('Project ID %d is not expected', $project_id));
            }
        );

        $result = $this->app_factory->getAppsAuthorizedByUser($user);
        $this->assertEquals([
            new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, $project_204),
        ], $result);
    }
}
