<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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
 *
 */

/**
 * @group UserTest
 */
class UserTest extends SOAPBase // phpcs:ignore
{

    public function setUp(): void
    {
        parent::setUp();

        $_SERVER['SERVER_NAME'] = $this->server_name;
        $_SERVER['SERVER_PORT'] = $this->server_port;
        $_SERVER['SCRIPT_NAME'] = $this->base_wsdl;
    }

    public function tearDown(): void
    {
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['SCRIPT_NAME']);

        parent::tearDown();
    }

    public function testGetUserInfo()
    {
        $session_hash = $this->getSessionHash();

        $test_user_1_id = $this->getUserID(SOAP_TestDataBuilder::TEST_USER_1_NAME);

        $response = $this->soap_base->getUserInfo(
            $session_hash,
            $test_user_1_id
        );

        $this->assertEquals($response->identifier, $test_user_1_id);
        $this->assertEquals($response->id, $test_user_1_id);
        $this->assertEquals($response->username, SOAP_TestDataBuilder::TEST_USER_1_NAME);
        $this->assertEquals($response->real_name, SOAP_TestDataBuilder::TEST_USER_1_REALNAME);
        $this->assertEquals($response->email, SOAP_TestDataBuilder::TEST_USER_1_EMAIL);
        $this->assertEquals($response->ldap_id, SOAP_TestDataBuilder::TEST_USER_1_LDAPID);
    }
}
