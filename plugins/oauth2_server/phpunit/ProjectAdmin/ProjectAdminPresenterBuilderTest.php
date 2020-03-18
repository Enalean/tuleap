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
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\LastCreatedOAuth2App;
use Tuleap\OAuth2Server\App\LastCreatedOAuth2AppStore;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\ProjectAdmin\AppPresenter;
use Tuleap\OAuth2Server\ProjectAdmin\LastCreatedOAuth2AppPresenter;
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
     * @var M\LegacyMockInterface|M\MockInterface|LastCreatedOAuth2AppStore
     */
    private $last_created_app_store;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppFactory
     */
    private $app_factory;

    protected function setUp(): void
    {
        $this->app_factory            = M::mock(AppFactory::class);
        $this->last_created_app_store = M::mock(LastCreatedOAuth2AppStore::class);
        $this->presenter_builder      = new ProjectAdminPresenterBuilder($this->app_factory, $this->last_created_app_store);
    }

    /**
     * @dataProvider dataProviderLastCreatedApp
     */
    public function testBuildTransformsAppsIntoPresenters(
        ?LastCreatedOAuth2App $last_created_app,
        ?LastCreatedOAuth2AppPresenter $expected_last_created_oauth2_presenter
    ): void {
        $project    = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn(102)
            ->getMock();
        $csrf_token = M::mock(\CSRFSynchronizerToken::class);
        $this->app_factory->shouldReceive('getAppsForProject')
            ->once()
            ->with($project)
            ->andReturn(
                [
                    new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, $project),
                    new OAuth2App(2, 'My custom REST client', 'https://my-custom-client.example.com', true, $project)
                ]
            );
        $this->last_created_app_store->shouldReceive('getLastCreatedApp')->once()->andReturn($last_created_app);

        $expected = new ProjectAdminPresenter(
            [
                new AppPresenter(1, 'Jenkins', 'https://jenkins.example.com', 'tlp-client-id-1', true),
                new AppPresenter(2, 'My custom REST client', 'https://my-custom-client.example.com', 'tlp-client-id-2', true)
            ],
            $csrf_token,
            $project,
            $expected_last_created_oauth2_presenter
        );
        $this->assertEquals($expected, $this->presenter_builder->build($csrf_token, $project));
    }

    public function dataProviderLastCreatedApp(): array
    {
        return [
            'No app has just been created' => [null, null],
            'An app has just been created' => [
                new LastCreatedOAuth2App(2, new ConcealedString('secret')),
                new LastCreatedOAuth2AppPresenter('tlp-client-id-2', new ConcealedString('secret'))
            ],
        ];
    }
}
