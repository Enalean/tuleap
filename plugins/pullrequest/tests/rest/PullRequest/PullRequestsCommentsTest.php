<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group PullRequest
 */
class PullRequestsCommentsTest extends RestBase {

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

    public function testOptions() {
        $response = $this->getResponse($this->client->options('pull_requests/1/comments'));

        $this->assertEquals(array('OPTIONS', 'GET', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGetPullRequestComments() {
        $response  = $this->getResponse($this->client->get('pull_requests/1/comments'));

        $pull_request_comments = $response->json();

        $this->assertEquals(3, count($pull_request_comments));
        $this->assertEquals(1, $pull_request_comments[0]['id']);
        $this->assertEquals('If the Easter Bunny and the Tooth Fairy had babies would they take your teeth and leave chocolate for you?', $pull_request_comments[0]['content']);
        $this->assertEquals(2, $pull_request_comments[1]['id']);
        $this->assertEquals('This is the last random sentence I will be writing and I am going to stop mid-sent', $pull_request_comments[1]['content']);
        $this->assertEquals(3, $pull_request_comments[2]['id']);
        $this->assertEquals('I am never at home on Sundays.', $pull_request_comments[2]['content']);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetPullRequestCommentsThrows403IfUserCantSeeGitRepository() {
        $response = $this->getResponseForNonMember($this->client->get('pull_requests/1/comments'));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testPostPullRequestComment() {
        $response  = $this->getResponse($this->client->post('pull_requests/1/comments', null, json_encode(
            array(
                'content' => 'Shot down in flames'
            )
        )));

        $this->assertEquals($response->getStatusCode(), 201);

        $pull_request_comment = $response->json();
        $this->assertEquals('Shot down in flames', $pull_request_comment['content']);
    }
}
