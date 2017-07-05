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
        $response = $this->getResponse($this->client->get('svn/1'));

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

    /**
     * @depends testGETRepositoryForProjectAdmin
     * @depends testGETRepositoryForProjectMember
     */
    public function testDELETERepositoryForProjectAdmin()
    {
        $response  = $this->getResponse($this->client->delete(
            'svn/1'
        ));

        $this->assertEquals($response->getStatusCode(), 202);
    }

    /**
     * @depends testGETRepositoryForProjectAdmin
     * @depends testGETRepositoryForProjectMember
     */
    public function testDELETERepositoryForProjectMember()
    {
        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
        $response = $this->getResponseWithProjectMember(
            $this->client->delete(
                'svn/1'
            )
        );
        $this->assertEquals($response->getStatusCode(), 401);
    }

    public function testPOSTRepositoryForProjectAdmin()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository"
            )
        );

        $response   = $this->getResponse($this->client->post('svn', null, $params));
        $repository = $response->json();

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'my_repository');
        $this->assertEquals(
            $repository['settings']['commit_rules'],
            array(
                "mandatory_reference"         => false,
                "allow_commit_message_change" => false
            )
        );
    }

    public function tesPOSTRepositoryForProjectMember()
    {
        $params = json_encode(
            array(
                "project_id" => $this->svn_project_id,
                "name"       => "my_repository"
            )
        );

        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
        $response = $this->getResponseWithProjectMember($this->client->post('svn', null, $params));
        $this->assertEquals($response->getStatusCode(), 401);
    }

    public function testPUTRepository()
    {
        $data = json_encode(
            array(
                'settings' => array(
                    'commit_rules' => array(
                        'mandatory_reference' => true,
                        'allow_commit_message_change' => false
                    )
                )
            )
        );

        $response = $this->getResponse($this->client->put('svn/1', null, $data));

        $repository = $response->json();

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals(
            $repository['settings']['commit_rules'],
            array(
                "mandatory_reference"         => true,
                "allow_commit_message_change" => false
            )
        );
    }

    public function testOPTIONS()
    {
        $response = $this->getResponse($this->client->get('svn/1'));

        $this->assertEquals(array('OPTIONS', 'GET', 'PUT', 'DELETE'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSId()
    {
        $response = $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $this->client->options('svn')
        );

        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }
}
