<?php
/**
 * Copyright (c) Enalean 2022-Present. All rights reserved
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

namespace Tuleap\SVNCore\AccessControl;

use Psr\Log\NullLogger;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use User_InvalidPasswordException;
use User_StatusInvalidException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SVNPasswordBasedAuthenticationMethodTest extends TestCase
{
    /**
     * @var \User_LoginManager&\PHPUnit\Framework\MockObject\Stub
     */
    private $login_manager;
    private SVNPasswordBasedAuthenticationMethod $auth_method;

    public function setUp(): void
    {
        $this->login_manager = $this->createStub(\User_LoginManager::class);
        $this->auth_method   = new SVNPasswordBasedAuthenticationMethod($this->login_manager, new NullLogger());
    }

    public function testAuthenticationCanBeSuccessful(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('username')->build();
        $this->login_manager->method('authenticate')->willReturn($user);
        $this->login_manager->method('validateAndSetCurrentUser');

        self::assertSame($user, $this->auth_method->isAuthenticated('username', new ConcealedString('valid_password'), ProjectTestBuilder::aProject()->build(), new NullServerRequest()));
    }

    public function testAuthenticationIsRejectedWhenUserCannotBeAuthenticatedWithThePassword(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('username')->build();
        $this->login_manager->method('authenticate')->willThrowException(new User_InvalidPasswordException());

        self::assertNull($this->auth_method->isAuthenticated('username', new ConcealedString('invalid_password'), ProjectTestBuilder::aProject()->build(), new NullServerRequest()));
    }

    public function testAuthenticationIsRejectedWhenUserCannotBeValidated(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('username')->build();
        $this->login_manager->method('authenticate')->willReturn($user);
        $this->login_manager->method('validateAndSetCurrentUser')->willThrowException(new User_StatusInvalidException());

        self::assertNull($this->auth_method->isAuthenticated('username', new ConcealedString('invalid_password'), ProjectTestBuilder::aProject()->build(), new NullServerRequest()));
    }
}
