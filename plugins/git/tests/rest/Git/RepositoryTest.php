<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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

namespace Git;

use GitDataBuilder;
use REST_TestDataBuilder;
use Tuleap\Git\REST\TestBase;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group GitTests
 */
class RepositoryTest extends TestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    protected function getResponseForNonMember($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_2_NAME),
            $request
        );
    }

    public function testGetGitRepository() {
        $response  = $this->getResponse($this->client->get(
            'git/'.GitDataBuilder::REPOSITORY_GIT_ID
        ));

        $repository = $response->json();

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
        $this->assertEquals($repository['description'], 'Git repository');
        $this->assertArrayHasKey('server', $repository);
    }

    public function testOPTIONS() {
        $response = $this->getResponse($this->client->options('git/'.GitDataBuilder::REPOSITORY_GIT_ID));
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetGitRepositoryThrows403IfUserCantSeeRepository() {
        $response = $this->getResponseForNonMember($this->client->get(
            'git/'.GitDataBuilder::REPOSITORY_GIT_ID
        ));

        $this->assertEquals($response->getStatusCode(), 403);
    }
}