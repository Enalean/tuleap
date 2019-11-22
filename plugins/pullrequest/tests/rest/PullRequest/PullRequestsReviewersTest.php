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

use REST_TestDataBuilder;
use RestBase;

final class PullRequestsReviewersTest extends RestBase
{
    public function testRetrievingUsersOnANonExistingPullRequest(): void
    {
        $response = $this->getResponse($this->client->get('pull_requests/99999999999/reviewers'));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGETReviewerUsersWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('pull_requests/1/reviewers'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->json()['users']);
    }

    public function testSetReviewers(): void
    {
        $update_response = $this->getResponse(
            $this->client->put(
                'pull_requests/1/reviewers',
                null,
                json_encode(
                    [
                        'users' => [
                            ['username' => REST_TestDataBuilder::TEST_USER_1_NAME]
                        ]
                    ],
                    JSON_THROW_ON_ERROR
                )
            ),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertEquals(204, $update_response->getStatusCode());

        $response_current_reviewers = $this->getResponse(
            $this->client->get('pull_requests/1/reviewers')
        );
        $this->assertEquals(200, $response_current_reviewers->getStatusCode());
        $current_reviewers = $response_current_reviewers->json();
        $this->assertCount(1, $current_reviewers['users']);
        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_NAME, $current_reviewers['users'][0]['username']);

        $clear_reviewer_response = $this->getResponse(
            $this->client->put(
                'pull_requests/1/reviewers',
                null,
                json_encode(['users' => []], JSON_THROW_ON_ERROR)
            )
        );
        $this->assertEquals(204, $clear_reviewer_response->getStatusCode());

        $response_cleared_reviewers = $this->getResponse(
            $this->client->get('pull_requests/1/reviewers')
        );
        $this->assertEquals(200, $response_cleared_reviewers->getStatusCode());
        $this->assertEmpty($response_cleared_reviewers->json()['users']);
    }

    public function testSetReviewerUsersWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->put(
                'pull_requests/1/reviewers',
                null,
                json_encode(['users' => []], JSON_THROW_ON_ERROR)
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
    }
}
