<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\User;

require_once __DIR__ . '/../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DynamicUserTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $language = \Mockery::mock(\BaseLanguage::class);
        $language->shouldReceive('getLanguageFromAcceptLanguage');
        $GLOBALS['Language'] = $language;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testUserIsConsideredAsActiveWhenLoggedIn()
    {
        $is_logged_in = true;
        $user         = new DynamicUser('Realname', [], $is_logged_in);

        $this->assertTrue($user->isActive());
    }

    public function testUserIsNotActiveWhenIsNotLoggedIn()
    {
        $is_logged_in = false;
        $user         = new DynamicUser('Realname', [], $is_logged_in);

        $this->assertFalse($user->isActive());
    }

    public function testUserIsSuperUser()
    {
        $is_logged_in = true;
        $user         = new DynamicUser('Realname', [], $is_logged_in);

        $this->assertTrue($user->isSuperUser());
    }

    public function testRealnameUsedIsTheGivenOne()
    {
        $is_logged_in = true;
        $user         = new DynamicUser('Alpaca', [], $is_logged_in);

        $this->assertEquals('Alpaca', $user->getRealName());
    }

    public function testSetValuesToUserDoesNothing()
    {
        $is_logged_in = false;
        $user         = new DynamicUser('Realname', [], $is_logged_in);

        $expected_password = $user->getPassword();
        $user->setPassword('password');
        $this->assertEquals($expected_password, $user->getPassword());

        $expected_username = $user->getUserName();
        $user->setUserName('username');
        $this->assertEquals($expected_username, $user->getUserName());

        $expected_status = $user->getStatus();
        $user->setStatus(\PFUser::STATUS_ACTIVE);
        $this->assertEquals($expected_status, $user->getStatus());

        $expected_unix_status = $user->getUnixStatus();
        $user->setUnixStatus('A');
        $this->assertEquals($expected_unix_status, $user->getUnixStatus());

        $expected_expiry_date = $user->getExpiryDate();
        $user->setExpiryDate(time());
        $this->assertEquals($expected_expiry_date, $user->getExpiryDate());
    }
}
