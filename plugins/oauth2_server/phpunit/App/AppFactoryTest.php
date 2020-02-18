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

    protected function setUp(): void
    {
        $this->app_dao     = M::mock(AppDao::class);
        $this->app_factory = new AppFactory($this->app_dao);
    }

    public function testGetAppsForProject(): void
    {
        $rows    = [
            ['id' => 1, 'name' => 'Jenkins'],
            ['id' => 2, 'name' => 'My custom REST client']
        ];
        $project = M::mock(\Project::class);
        $this->app_dao->shouldReceive('searchByProject')
            ->once()
            ->with($project)
            ->andReturn($rows);

        $result = $this->app_factory->getAppsForProject($project);
        $this->assertEquals(
            [new OAuth2App(1, 'Jenkins', $project), new OAuth2App(2, 'My custom REST client', $project)],
            $result
        );
    }
}
