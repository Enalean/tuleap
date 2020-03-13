<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

namespace Tuleap\PullRequest;

use REST_TestDataBuilder;
use RestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group PullRequest
 */
class PullRequestsTest extends RestBase
{
    protected function getResponseForNonMember($request)
    {
        return $this->getResponse($request, REST_TestDataBuilder::TEST_USER_2_NAME);
    }

    // some routes require a major refactoring of REST tests initialization
    // and thus will be done in a following commit

    //public function testGetPullRequest() {
    //    $response  = $this->getResponse($this->client->get('pull_requests/1'));

    //    $pull_request = $response->json();

    //    $this->assertEquals(1, $pull_request['id']);
    //    $this->assertEquals(102, $pull_request['user_id']);
    //    $this->assertEquals(1, $pull_request['repository']['id']);
    //    $this->assertEquals('dev', $pull_request['branch_src']);
    //    $this->assertEquals('master', $pull_request['branch_dest']);
    //}

    public function testOPTIONS()
    {
        $response = $this->getResponse($this->client->options('pull_requests/'));

        $this->assertEquals(array('OPTIONS', 'GET', 'POST', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSWithReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->client->options('pull_requests/'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET', 'POST', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGetPullRequestThrows403IfUserCantSeeGitRepository()
    {
        $response = $this->getResponseForNonMember($this->client->get('pull_requests/1'));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    // public function testPATCHPullRequestToAbandonAPullRequest() {
    //     $data = json_encode(array(
    //         'status' => 'abandon'
    //     ));

    //     $response = $this->getResponse($this->client->patch(
    //         'pull_requests/1',
    //         null,
    //         $data
    //     ));

    //     $this->assertEquals($response->getStatusCode(), 200);

    //     // some routes require a major refactoring of REST tests initialization
    //     // and thus will be done in a following commit
    //     $response_get = $this->getResponse($this->client->get('pull_requests/1'));
    //     $pull_request = $response_get->json();

    //     $this->assertEquals('abandon', $pull_request['status']);
    // }

    public function testPATCHPullRequestThrow400IfStatusIsUnknown()
    {
        $data = json_encode(array(
            'status' => 'whatever'
        ));

        $response = $this->getResponse($this->client->patch(
            'pull_requests/1',
            null,
            $data
        ));

        $this->assertEquals(400, $response->getStatusCode());
    }
}
