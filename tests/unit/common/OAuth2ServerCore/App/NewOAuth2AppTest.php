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

namespace Tuleap\OAuth2ServerCore\App;

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewOAuth2AppTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidData')]
    public function testFromAppDataThrowsWhenDataIsInvalid(string $app_name, string $redirect_uri): void
    {
        $this->expectException(InvalidAppDataException::class);
        NewOAuth2App::fromProjectAdministrationAppData(
            $app_name,
            $redirect_uri,
            true,
            ProjectTestBuilder::aProject()->build(),
            new SplitTokenVerificationStringHasher(),
            'plugin_oauth2'
        );
    }

    public static function dataProviderInvalidData(): array
    {
        return [
            'Throws when App name is empty'               => ['', 'https://example.com/redirect'],
            'Throws when Redirect URI is empty'           => ['Jenkins', ''],
            'Throws when Redirect URI is not HTTPS'       => ['Jenkins', 'http://insecure.example.com'],
            'Throws when Redirect URI is not localhost'   => ['Jenkins', 'http://localhost.example.com'],
            'Throws when Redirect URI contains an anchor' => ['Jenkins', 'https://example.com/redirect#fragment'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidData')]
    public function testFromProjectAdminAppDataReturnsANewOauth2AppToBeSavedInDatabase(
        string $redirect_uri,
        bool $use_pkce,
    ): void {
        $app_name = 'Jenkins';
        $project  = ProjectTestBuilder::aProject()->build();
        $new_app  = NewOAuth2App::fromProjectAdministrationAppData(
            $app_name,
            $redirect_uri,
            $use_pkce,
            $project,
            new SplitTokenVerificationStringHasher(),
            'plugin_oauth2'
        );
        self::assertSame($app_name, $new_app->getName());
        self::assertSame($redirect_uri, $new_app->getRedirectEndpoint());
        self::assertSame($use_pkce, $new_app->isUsingPKCE());
        self::assertSame($project, $new_app->getProject());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidData')]
    public function testFromSiteAdminAppDataReturnsANewOauth2AppToBeSavedInDatabase(
        string $redirect_uri,
        bool $use_pkce,
    ): void {
        $app_name = 'Jenkins';
        $new_app  = NewOAuth2App::fromSiteAdministrationAppData(
            $app_name,
            $redirect_uri,
            $use_pkce,
            new SplitTokenVerificationStringHasher(),
            'plugin_oauth2'
        );
        self::assertSame($app_name, $new_app->getName());
        self::assertSame($redirect_uri, $new_app->getRedirectEndpoint());
        self::assertSame($use_pkce, $new_app->isUsingPKCE());
        $this->assertNull($new_app->getProject());
    }

    public static function dataProviderValidData(): array
    {
        return [
            'Valid data'                         => ['https://example.com/redirect', false],
            'Valid data with localhost'          => ['http://localhost', false],
            'Valid data with localhost and port' => ['http://localhost:40000/redirect', false],
            'Valid with query in redirect URI'   => ['https://example.com/redirect?key=value', true],
        ];
    }

    public function testNewAppSecretCanBeHashed(): void
    {
        $hasher  = new SplitTokenVerificationStringHasher();
        $new_app = NewOAuth2App::fromProjectAdministrationAppData('App', 'https://example.com', true, ProjectTestBuilder::aProject()->build(), $hasher, 'plugin_oauth2');

        $this->assertEquals($hasher->computeHash($new_app->getSecret()), $new_app->getHashedSecret());
    }

    public function testEachNewAppIsAssignedADifferentSecret(): void
    {
        $hasher    = new SplitTokenVerificationStringHasher();
        $new_app_1 = NewOAuth2App::fromProjectAdministrationAppData('App1', 'https://example.com', true, ProjectTestBuilder::aProject()->build(), $hasher, 'plugin_oauth2');
        $new_app_2 = NewOAuth2App::fromProjectAdministrationAppData('App2', 'https://example.com', true, ProjectTestBuilder::aProject()->build(), $hasher, 'plugin_oauth2');

        $this->assertNotEquals($new_app_1->getSecret(), $new_app_2->getSecret());
        $this->assertNotEquals($new_app_1->getHashedSecret(), $new_app_2->getHashedSecret());
    }
}
