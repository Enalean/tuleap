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

final class NewOAuth2AppTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFromAppDataThrowsWhenAppNameIsEmpty(): void
    {
        $this->expectException(InvalidAppDataException::class);
        NewOAuth2App::fromAppData('', 'https://example.com/redirect', M::mock(\Project::class));
    }

    public function testFromAppDataThrowsWhenRedirectEndpointIsEmpty(): void
    {
        $this->expectException(InvalidAppDataException::class);
        NewOAuth2App::fromAppData('Jenkins', '', M::mock(\Project::class));
    }

    public function testFromAppDataThrowsWhenRedirectEndpointIsNotHTTPS(): void
    {
        $this->expectException(InvalidAppDataException::class);
        NewOAuth2App::fromAppData('Jenkins', 'http://insecure.example.com', M::mock(\Project::class));
    }

    public function testFromAppDataThrowsWhenRedirectEndpointContainsAnAnchor(): void
    {
        $this->expectException(InvalidAppDataException::class);
        NewOAuth2App::fromAppData('Jenkins', 'https://example.com/redirect#fragment', M::mock(\Project::class));
    }

    public function testFromAppDataReturnsANewOauth2AppToBeSavedInDatabase(): void
    {
        $app_name          = 'Jenkins';
        $redirect_endpoint = 'https://example.com/redirect';
        $project           = M::mock(\Project::class);
        $new_app           = NewOAuth2App::fromAppData($app_name, $redirect_endpoint, $project);
        $this->assertSame($app_name, $new_app->getName());
        $this->assertSame($redirect_endpoint, $new_app->getRedirectEndpoint());
        $this->assertSame($project, $new_app->getProject());
    }

    public function testFromAppDataAllowsRedirectEndpointWithQuery(): void
    {
        $redirect_endpoint = 'https://example.com/redirect?key=value';
        $new_app = NewOAuth2App::fromAppData('Jenkins', $redirect_endpoint, M::mock(\Project::class));
        $this->assertSame($redirect_endpoint, $new_app->getRedirectEndpoint());
    }
}
