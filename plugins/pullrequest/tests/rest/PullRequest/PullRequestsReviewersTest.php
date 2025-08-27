<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\PullRequest;

use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\REST\RestBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PullRequestsReviewersTest extends RestBase
{
    public function testRetrievingUsersOnANonExistingPullRequest(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'pull_requests/99999999999/reviewers'));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGETReviewerUsersWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'pull_requests/1/reviewers'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['users']);
    }

    public function testSetReviewers(): void
    {
        $update_response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'pull_requests/1/reviewers')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'users' => [
                        ['username' => RESTTestDataBuilder::TEST_USER_1_NAME],
                    ],
                ],
                JSON_THROW_ON_ERROR
            ))),
            RESTTestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertEquals(204, $update_response->getStatusCode());

        $response_current_reviewers = $this->getResponse(
            $this->request_factory->createRequest('GET', 'pull_requests/1/reviewers')
        );
        $this->assertEquals(200, $response_current_reviewers->getStatusCode());
        $current_reviewers = json_decode($response_current_reviewers->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $current_reviewers['users']);
        $this->assertEquals(RESTTestDataBuilder::TEST_USER_1_NAME, $current_reviewers['users'][0]['username']);

        $clear_reviewer_response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'pull_requests/1/reviewers')->withBody($this->stream_factory->createStream(json_encode(['users' => []], JSON_THROW_ON_ERROR)))
        );
        $this->assertEquals(204, $clear_reviewer_response->getStatusCode());

        $response_cleared_reviewers = $this->getResponse(
            $this->request_factory->createRequest('GET', 'pull_requests/1/reviewers')
        );
        $this->assertEquals(200, $response_cleared_reviewers->getStatusCode());
        $this->assertEmpty(json_decode($response_cleared_reviewers->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['users']);
    }

    public function testSetReviewerUsersWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'pull_requests/1/reviewers')->withBody($this->stream_factory->createStream(json_encode(['users' => []], JSON_THROW_ON_ERROR))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
    }
}
