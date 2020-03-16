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

use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;
use RestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group PullRequest
 */
final class PullRequestsCommentsTest extends RestBase
{

    protected function getResponseForNonMember($request)
    {
        return $this->getResponse($request, REST_TestDataBuilder::TEST_USER_2_NAME);
    }

    public function testOptions(): void
    {
        $response = $this->getResponse($this->client->options('pull_requests/1/comments'));

        $this->assertEquals(array('OPTIONS', 'GET', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOptionsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('pull_requests/1/comments'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGetPullRequestComments(): void
    {
        $response = $this->getResponse($this->client->get('pull_requests/1/comments'));

        $this->assertGETPullRequestsComments($response);
    }

    public function testGetPullRequestCommentsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('pull_requests/1/comments'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETPullRequestsComments($response);
    }

    private function assertGETPullRequestsComments(Response $response): void
    {
        $pull_request_comments = $response->json();

        $this->assertEquals(3, count($pull_request_comments));
        $this->assertEquals(1, $pull_request_comments[0]['id']);
        $this->assertEquals('If the Easter Bunny and the Tooth Fairy had babies would they take your teeth and leave chocolate for you?', $pull_request_comments[0]['content']);
        $this->assertEquals(2, $pull_request_comments[1]['id']);
        $this->assertEquals('This is the last random sentence I will be writing and I am going to stop mid-sent', $pull_request_comments[1]['content']);
        $this->assertEquals(3, $pull_request_comments[2]['id']);
        $this->assertEquals('I am never at home on Sundays.', $pull_request_comments[2]['content']);
    }

    public function testGetPullRequestCommentsThrows403IfUserCantSeeGitRepository(): void
    {
        $response = $this->getResponseForNonMember($this->client->get('pull_requests/1/comments'));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testPostPullRequestComment(): void
    {
        $response  = $this->getResponse($this->client->post('pull_requests/1/comments', null, json_encode(
            array(
                'content' => 'Shot down in flames'
            )
        )));

        $this->assertEquals($response->getStatusCode(), 201);

        $pull_request_comment = $response->json();
        $this->assertEquals('Shot down in flames', $pull_request_comment['content']);
    }

    public function testPostPullRequestCommentWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->post(
                'pull_requests/1/comments',
                null,
                json_encode(
                    array(
                        'content' => 'Shot down in flames'
                    )
                )
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}
