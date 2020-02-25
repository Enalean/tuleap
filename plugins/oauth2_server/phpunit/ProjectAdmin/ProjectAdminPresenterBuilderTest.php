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

namespace ProjectAdmin;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\ProjectAdmin\AppPresenter;
use Tuleap\OAuth2Server\ProjectAdmin\ProjectAdminPresenter;
use Tuleap\OAuth2Server\ProjectAdmin\ProjectAdminPresenterBuilder;

final class ProjectAdminPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectAdminPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppFactory
     */
    private $app_factory;

    protected function setUp(): void
    {
        $this->app_factory       = M::mock(AppFactory::class);
        $this->presenter_builder = new ProjectAdminPresenterBuilder($this->app_factory);
    }

    public function testBuildTransformsAppsIntoPresenters(): void
    {
        $project    = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn(102)
            ->getMock();
        $csrf_token = M::mock(\CSRFSynchronizerToken::class);
        $this->app_factory->shouldReceive('getAppsForProject')
            ->once()
            ->with($project)
            ->andReturn(
                [
                    new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', $project),
                    new OAuth2App(2, 'My custom REST client', 'https://my-custom-client.example.com', $project)
                ]
            );

        $expected = new ProjectAdminPresenter(
            [
                new AppPresenter(1, 'Jenkins', 'https://jenkins.example.com', 'tlp-client-id-1'),
                new AppPresenter(2, 'My custom REST client', 'https://my-custom-client.example.com', 'tlp-client-id-2')
            ],
            $csrf_token,
            $project
        );
        $this->assertEquals($expected, $this->presenter_builder->build($csrf_token, $project));
    }
}
