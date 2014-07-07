<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group UserGroupTests
 */
class UsersTest extends RestBase {

    public function testGETId() {
        $response = $this->client->get('users/'.TestDataBuilder::TEST_USER_1_ID)->send();

        $this->assertEquals(
            $response->json(),
            array(
                'id'         => TestDataBuilder::TEST_USER_1_ID,
                'uri'        => 'users/'.TestDataBuilder::TEST_USER_1_ID,
                'email'      => TestDataBuilder::TEST_USER_1_EMAIL,
                'real_name'  => TestDataBuilder::TEST_USER_1_REALNAME,
                'username'   => TestDataBuilder::TEST_USER_1_NAME,
                'ldap_id'    => TestDataBuilder::TEST_USER_1_LDAPID,
                'avatar_url' => '/themes/common/images/avatar_default.png'
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGETIdDoesNotWorkIfUserDoesNotExist() {
        $this->client->get('users/1')->send();
    }
}