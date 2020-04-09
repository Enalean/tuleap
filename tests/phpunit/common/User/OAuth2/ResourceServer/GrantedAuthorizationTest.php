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

namespace Tuleap\User\OAuth2\ResourceServer;

use PHPUnit\Framework\TestCase;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OpenIDConnectEmailScope;
use Tuleap\Test\Builders\UserTestBuilder;

final class GrantedAuthorizationTest extends TestCase
{
    public function testReturnsGivenValues(): void
    {
        $user                  = UserTestBuilder::aUser()->build();
        $scopes                = [OAuth2SignInScope::fromItself(), OpenIDConnectEmailScope::fromItself()];
        $granted_authorization = new GrantedAuthorization($user, $scopes);

        $this->assertSame($scopes, $granted_authorization->getScopes());
        $this->assertSame($user, $granted_authorization->getUser());
    }
}
