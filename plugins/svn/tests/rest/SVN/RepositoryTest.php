<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVN\REST;

use REST_TestDataBuilder;

require_once dirname(__FILE__).'/../bootstrap.php';

class RepositoryTest extends TestBase
{
    protected function getResponse($request)
    {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    private function getResponseWithProjectMember($request)
    {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_3_NAME),
            $request
        );
    }

    public function testGETRepositoryForProjectAdmin()
    {
        $response = $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $this->client->get('svn/1')
        );

        $repository = $response->json();

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertArrayHasKey('settings', $repository);
        $this->assertEquals(
            $repository['settings']['commit_rules'],
            array(
                "mandatory_reference"         => false,
                "allow_commit_message_change" => false
            )
        );
    }

    public function testGETRepositoryForProjectMember()
    {
        $response = $this->getResponseWithProjectMember($this->client->get('svn/1'));

        $repository = $response->json();

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertArrayNotHasKey('settings', $repository);
    }

    public function testOPTIONS()
    {
        $response = $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $this->client->get('svn/1')
        );

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }
}
