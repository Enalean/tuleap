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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

use PHPUnit\Framework\TestCase;
use Tuleap\User\OAuth2\Scope\DemoOAuth2Scope;

final class OAuth2AuthorizationCodeTest extends TestCase
{
    public function testBuildValidAuthorizationCodeWithDemoScope(): void
    {
        $user      = new \PFUser(['language_id' => 'en']);
        $auth_code = OAuth2AuthorizationCode::approveForDemoScope($user);

        $this->assertSame($user, $auth_code->getUser());
        $scopes = $auth_code->getScopes();
        $this->assertCount(1, $scopes);
        $this->assertEquals(DemoOAuth2Scope::fromItself(), $scopes[0]);
    }
}
