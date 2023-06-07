<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

namespace Tuleap\LDAP;

use LDAP_UserSync;
use PFUser;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;

/**
 * @covers \LDAP_UserSync
 */
final class UserSyncTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testNoUpdateWhenNoDifference(): void
    {
        $user = UserTestBuilder::aUser()->withEmail('toto@example.com')->withRealName('toto')->build();
        $lr   = new \LDAPResult(
            [
                'cn' => ['toto'],
                'mail' => ['toto@example.com'],
            ],
            [
                'cn' => 'cn',
                'mail' => 'mail',
            ],
        );

        $sync = new LDAP_UserSync(new NullLogger());

        self::assertFalse($sync->sync($user, $lr));
    }

    public function testNoUpdateWhenNoDifferenceWithLongName(): void
    {
        $user = UserTestBuilder::aUser()->withEmail('toto@example.com')->withRealName('totooooooooooooooooooooooooooooooooooooo')->build();
        $lr   = new \LDAPResult(
            [
                'cn' => ['totooooooooooooooooooooooooooooooooooooo'],
                'mail' => ['toto@example.com'],
            ],
            [
                'cn' => 'cn',
                'mail' => 'mail',
            ],
        );

        $sync = new LDAP_UserSync(new NullLogger());

        self::assertFalse($sync->sync($user, $lr));
    }

    public function testUserUpdateEmailIfLdapDoesntMatch(): void
    {
        $user = UserTestBuilder::aUser()->withEmail('toto@example.com')->withRealName('toto')->build();
        $lr   = new \LDAPResult(
            [
                'cn' => ['toto'],
                'mail' => ['foobar@example.com'],
            ],
            [
                'cn' => 'cn',
                'mail' => 'mail',
            ],
        );

        $sync = new LDAP_UserSync(new NullLogger());

        self::assertTrue($sync->sync($user, $lr));
        self::assertSame('foobar@example.com', $user->getEmail());
        self::assertSame('toto', $user->getRealName());
    }

    public function testUserUpdateRealnameIfLdapDoesntMatch(): void
    {
        $user = UserTestBuilder::aUser()->withEmail('toto@example.com')->withRealName('toto')->build();
        $lr   = new \LDAPResult(
            [
                'cn' => ['foobar'],
                'mail' => ['toto@example.com'],
            ],
            [
                'cn' => 'cn',
                'mail' => 'mail',
            ],
        );

        $sync = new LDAP_UserSync(new NullLogger());

        self::assertTrue($sync->sync($user, $lr));
        self::assertSame('foobar', $user->getRealName());
        self::assertSame('toto@example.com', $user->getEmail());
    }

    public function testChangeUserStatusWithDedicatedCode(): void
    {
        $user = UserTestBuilder::aUser()->withEmail('toto@example.com')->withRealName('toto')->withStatus(PFUser::STATUS_ACTIVE)->build();
        $lr   = new \LDAPResult(
            [
                'cn' => ['toto'],
                'mail' => ['toto@example.com'],
                'employeetype' => ['contractor'],
            ],
            [
                'cn' => 'cn',
                'mail' => 'mail',
                'employeetype' => 'employeetype',
            ],
        );

        include_once __DIR__ . '/../../site-content/en_US/synchronize_user.txt';
        $sync = new \LDAPPluginCustomUserSync();

        self::assertTrue($sync->sync($user, $lr));
        self::assertSame(PFUser::STATUS_RESTRICTED, $user->getStatus());
        self::assertSame('toto', $user->getRealName());
        self::assertSame('toto@example.com', $user->getEmail());
    }
}
