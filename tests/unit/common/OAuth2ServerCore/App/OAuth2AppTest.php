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

namespace Tuleap\OAuth2ServerCore\App;

use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class OAuth2AppTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidData')]
    public function testFromAppDataThrowsWhenDataIsInvalid(string $app_id, string $app_name): void
    {
        $this->expectException(InvalidAppDataException::class);
        OAuth2App::fromProjectAdministrationData($app_id, $app_name, '', true, ProjectTestBuilder::aProject()->build());
    }

    public static function dataProviderInvalidData(): array
    {
        return [
            'Throws when App ID is empty'                 => ['', 'Jenkins'],
            'Throws when App ID is not numeric'           => ['a', 'Jenkins'],
            'Throws when App name is empty'               => ['75', ''],
            'Throws when Redirect URI is empty'           => ['75', 'Jenkins'],
            'Throws when Redirect URI is not HTTPS'       => ['75', 'Jenkins'],
            'Throws when Redirect URI is not localhost'   => ['75', 'Jenkins'],
            'Throws when Redirect URI contains an anchor' => ['75', 'Jenkins'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidData')]
    public function testFromProjectAdministrationDataReturnsAnUpdatedOAuth2AppToBeSavedInDatabase(
        string $redirect_uri,
        bool $use_pkce,
    ): void {
        $app_id      = '75';
        $app_name    = 'Jenkins';
        $project     = ProjectTestBuilder::aProject()->build();
        $updated_app = OAuth2App::fromProjectAdministrationData($app_id, $app_name, $redirect_uri, $use_pkce, $project);
        self::assertSame(75, $updated_app->getId());
        self::assertSame($app_name, $updated_app->getName());
        self::assertSame($redirect_uri, $updated_app->getRedirectEndpoint());
        self::assertSame($use_pkce, $updated_app->isUsingPKCE());
        self::assertSame($project, $updated_app->getProject());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidData')]
    public function testFromSiteAdministrationDataReturnsAnUpdatedOAuth2AppToBeSavedInDatabase(
        string $redirect_uri,
        bool $use_pkce,
    ): void {
        $app_id      = '75';
        $app_name    = 'Jenkins';
        $updated_app = OAuth2App::fromSiteAdministrationData($app_id, $app_name, $redirect_uri, $use_pkce);
        self::assertSame(75, $updated_app->getId());
        self::assertSame($app_name, $updated_app->getName());
        self::assertSame($redirect_uri, $updated_app->getRedirectEndpoint());
        self::assertSame($use_pkce, $updated_app->isUsingPKCE());
        $this->assertNull($updated_app->getProject());
    }

    public static function dataProviderValidData(): array
    {
        return [
            'Valid data'                         => ['https://example.com/redirect', false],
            'Valid with query in redirect URI'   => ['https://example.com/redirect?key=value', true],
            'Valid data with localhost'          => ['http://localhost', false],
            'Valid data with localhost and port' => ['http://localhost:40000/redirect', false],
        ];
    }
}
