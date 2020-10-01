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

namespace Tuleap\OAuth2Server\Administration;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\LastGeneratedClientSecret;
use Tuleap\OAuth2Server\App\LastGeneratedClientSecretStore;
use Tuleap\OAuth2Server\App\OAuth2App;

final class AdminOAuth2AppsPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AdminOAuth2AppsPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|LastGeneratedClientSecretStore
     */
    private $client_secret_store;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppFactory
     */
    private $app_factory;

    protected function setUp(): void
    {
        $this->app_factory = M::mock(AppFactory::class);
        $this->client_secret_store = M::mock(LastGeneratedClientSecretStore::class);
        $this->presenter_builder = new AdminOAuth2AppsPresenterBuilder($this->app_factory, $this->client_secret_store);
    }

    /**
     * @dataProvider dataProviderLastCreatedApp
     */
    public function testBuildTransformsProjectAppsIntoPresenters(
        ?LastGeneratedClientSecret $last_generated_client_secret,
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
        $this->client_secret_store->shouldReceive('getLastGeneratedClientSecret')->once()->andReturn(
            $last_generated_client_secret
        );

        $expected = AdminOAuth2AppsPresenter::forProjectAdministration(
            $project,
            [
                new AppPresenter(1, 'Jenkins', 'https://jenkins.example.com', 'tlp-client-id-1', true),
                new AppPresenter(2, 'My custom REST client', 'https://my-custom-client.example.com', 'tlp-client-id-2', true)
            ],
            $csrf_token,
            $expected_last_created_oauth2_presenter
        );
        $this->assertEquals($expected, $this->presenter_builder->buildProjectAdministration($csrf_token, $project));
    }

    /**
     * @dataProvider dataProviderLastCreatedApp
     */
    public function testBuildTransformsSiteAppsIntoPresenters(
        ?LastGeneratedClientSecret $last_generated_client_secret,
        ?LastCreatedOAuth2AppPresenter $expected_last_created_oauth2_presenter
    ): void {
        $csrf_token = M::mock(\CSRFSynchronizerToken::class);
        $this->app_factory->shouldReceive('getSiteLevelApps')
            ->once()
            ->andReturn(
                [
                    new OAuth2App(1, 'Jenkins', 'https://jenkins.example.com', true, null),
                    new OAuth2App(2, 'My custom REST client', 'https://my-custom-client.example.com', true, null)
                ]
            );
        $this->client_secret_store->shouldReceive('getLastGeneratedClientSecret')->once()->andReturn(
            $last_generated_client_secret
        );

        $expected = AdminOAuth2AppsPresenter::forSiteAdministration(
            [
                new AppPresenter(1, 'Jenkins', 'https://jenkins.example.com', 'tlp-client-id-1', true),
                new AppPresenter(2, 'My custom REST client', 'https://my-custom-client.example.com', 'tlp-client-id-2', true)
            ],
            $csrf_token,
            $expected_last_created_oauth2_presenter
        );
        $this->assertEquals($expected, $this->presenter_builder->buildSiteAdministration($csrf_token));
    }

    public function dataProviderLastCreatedApp(): array
    {
        return [
            'No app has just been created' => [null, null],
            'An app has just been created' => [
                new LastGeneratedClientSecret(2, new ConcealedString('secret')),
                new LastCreatedOAuth2AppPresenter('tlp-client-id-2', new ConcealedString('secret'))
            ],
        ];
    }
}
