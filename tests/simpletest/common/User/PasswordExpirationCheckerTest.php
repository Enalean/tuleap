<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class User_PasswordExpirationCheckerTest extends TuleapTestCase
{
    private $password_expiration_checker;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        ForgeConfig::store();
        $this->password_expiration_checker = new User_PasswordExpirationChecker();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itRaisesAnExceptionWhenPasswordExpired()
    {
        $this->expectException('User_PasswordExpiredException');
        ForgeConfig::set('sys_password_lifetime', 10);
        $this->password_expiration_checker->checkPasswordLifetime(aUser()
                ->withPassword('password')
                ->withStatus(PFUser::STATUS_ACTIVE)
                ->withLastPasswordUpdate(strtotime('15 days ago'))
                ->build());
    }
}
