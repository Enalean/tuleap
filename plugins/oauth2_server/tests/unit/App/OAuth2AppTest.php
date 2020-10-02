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

namespace Tuleap\OAuth2Server\App;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class OAuth2AppTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider dataProviderInvalidData
     */
    public function testFromAppDataThrowsWhenDataIsInvalid(string $app_id, string $app_name): void
    {
        $this->expectException(InvalidAppDataException::class);
        OAuth2App::fromProjectAdministrationData($app_id, $app_name, '', true, M::mock(\Project::class));
    }

    public function dataProviderInvalidData(): array
    {
        return [
            'Throws when App ID is empty'                 => ['', 'Jenkins', 'https://example.com/redirect'],
            'Throws when App ID is not numeric'           => ['a', 'Jenkins', 'https://example.com/redirect'],
            'Throws when App name is empty'               => ['75', '', 'https://example.com/redirect'],
            'Throws when Redirect URI is empty'           => ['75', 'Jenkins', ''],
            'Throws when Redirect URI is not HTTPS'       => ['75', 'Jenkins', 'http://insecure.example.com'],
            'Throws when Redirect URI contains an anchor' => ['75', 'Jenkins', 'https://example.com/redirect#fragment']
        ];
    }

    /**
     * @dataProvider dataProviderValidData
     */
    public function testFromProjectAdministrationDataReturnsAnUpdatedOAuth2AppToBeSavedInDatabase(
        string $redirect_uri,
        bool $use_pkce
    ): void {
        $app_id      = '75';
        $app_name    = 'Jenkins';
        $project     = M::mock(\Project::class);
        $updated_app = OAuth2App::fromProjectAdministrationData($app_id, $app_name, $redirect_uri, $use_pkce, $project);
        $this->assertSame(75, $updated_app->getId());
        $this->assertSame($app_name, $updated_app->getName());
        $this->assertSame($redirect_uri, $updated_app->getRedirectEndpoint());
        $this->assertSame($use_pkce, $updated_app->isUsingPKCE());
        $this->assertSame($project, $updated_app->getProject());
    }

    /**
     * @dataProvider dataProviderValidData
     */
    public function testFromSiteAdministrationDataReturnsAnUpdatedOAuth2AppToBeSavedInDatabase(
        string $redirect_uri,
        bool $use_pkce
    ): void {
        $app_id      = '75';
        $app_name    = 'Jenkins';
        $updated_app = OAuth2App::fromSiteAdministrationData($app_id, $app_name, $redirect_uri, $use_pkce);
        $this->assertSame(75, $updated_app->getId());
        $this->assertSame($app_name, $updated_app->getName());
        $this->assertSame($redirect_uri, $updated_app->getRedirectEndpoint());
        $this->assertSame($use_pkce, $updated_app->isUsingPKCE());
        $this->assertNull($updated_app->getProject());
    }

    public function dataProviderValidData(): array
    {
        return [
            'Valid data'                       => ['https://example.com/redirect', false],
            'Valid with query in redirect URI' => ['https://example.com/redirect?key=value', true]
        ];
    }
}
