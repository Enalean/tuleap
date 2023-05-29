<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\User;

require_once __DIR__ . '/../bootstrap.php';

use Tuleap\Cryptography\ConcealedString;
use Tuleap\GlobalLanguageMock;

final class DynamicUserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['Language']->method('getLanguageFromAcceptLanguage');
    }

    public function testUserIsConsideredAsActiveWhenLoggedIn(): void
    {
        $is_logged_in = true;
        $user         = new DynamicUser('Realname', [], $is_logged_in);

        self::assertTrue($user->isActive());
    }

    public function testUserIsNotActiveWhenIsNotLoggedIn(): void
    {
        $is_logged_in = false;
        $user         = new DynamicUser('Realname', [], $is_logged_in);

        self::assertFalse($user->isActive());
    }

    public function testUserIsSuperUser(): void
    {
        $is_logged_in = true;
        $user         = new DynamicUser('Realname', [], $is_logged_in);

        self::assertTrue($user->isSuperUser());
    }

    public function testRealnameUsedIsTheGivenOne(): void
    {
        $is_logged_in = true;
        $user         = new DynamicUser('Alpaca', [], $is_logged_in);

        self::assertEquals('Alpaca', $user->getRealName());
    }

    public function testSetValuesToUserDoesNothing(): void
    {
        $is_logged_in = false;
        $user         = new DynamicUser('Realname', [], $is_logged_in);

        $expected_password = $user->getPassword();
        $user->setPassword(new ConcealedString('password'));
        self::assertEquals($expected_password, $user->getPassword());

        $expected_username = $user->getUserName();
        $user->setUserName('username');
        self::assertEquals($expected_username, $user->getUserName());

        $expected_status = $user->getStatus();
        $user->setStatus(\PFUser::STATUS_ACTIVE);
        self::assertEquals($expected_status, $user->getStatus());

        $expected_unix_status = $user->getUnixStatus();
        $user->setUnixStatus('A');
        self::assertEquals($expected_unix_status, $user->getUnixStatus());

        $expected_expiry_date = $user->getExpiryDate();
        $user->setExpiryDate(time());
        self::assertEquals($expected_expiry_date, $user->getExpiryDate());
    }
}
