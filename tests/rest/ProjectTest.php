<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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

require_once dirname(__FILE__).'/../lib/autoload.php';
use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;

/**
 * @group ProjectTests
 */
class ProjectTest extends RestBase {

    /**
     * @var Client
     */
    private $client;

    public function setUp() {
        parent::setUp();

        $this->client = new Client($this->base_url);
    }

    public function testGETbyIdForAdmin() {
        $user    = $this->getUserByName(TestDataBuilder::ADMIN_USER_NAME);
        $token   = $this->generateToken($user);

        $request = $this->client->get('/api/v1/projects/101')
                ->setHeader('X-Auth-Token', $token->getTokenValue())
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('X-Auth-UserId', $token->getUserId());

        $response = $request->send();

        $this->assertEquals($response->json(), array(
            'id'        => '101',
            'uri'       => 'projects/101',
            'label'     => TestDataBuilder::TEST_PROJECT_LONG_NAME,
            'resources' => array(
                'projects/101/plannings'
            ))
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETbyIdForForbiddenUser() {
        $user    = $this->getUserByName(TestDataBuilder::TEST_USER_NAME);
        $token   = $this->generateToken($user);

        $request = $this->client->get('/api/v1/projects/100')
                ->setHeader('X-Auth-Token', $token->getTokenValue())
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('X-Auth-UserId', $token->getUserId());

        //not very nice for a test but that's how guzzle works.
        $exception = false;
        try {
            $request->send();
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 403);
            $exception = true;
        }

        $this->assertTrue($exception);
    }
}
?>