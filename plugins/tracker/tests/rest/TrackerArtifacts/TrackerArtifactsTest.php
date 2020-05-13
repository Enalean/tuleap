<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Tests\REST\TrackerArtifacts;

use REST_TestDataBuilder;
use Tuleap\Tracker\REST\DataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

class TrackerArtifactsTest extends TrackerBase
{
    public function testQueryFiltersOnSubmittedByFieldWithValidUserId(): void
    {
        $query = [
            'submitted_by' => [
                'operator' => 'contains',
                'value' => $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME]
            ]
        ];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('/api/v1/trackers/' . $this->tracker_artifacts_tracker_id . '/artifacts?limit=100&query=' . urlencode(json_encode($query)))
        );

        $artifacts = $response->json();

        $this->assertCount(1, $artifacts);

        $artifact = $artifacts[0];

        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_NAME, $artifact['submitted_by_user']['username']);
    }

    public function testQueryFiltersOnSubmittedByFieldDoesNotReturnAllArtifactsWhenQueryContainsWrongUserId(): void
    {
        $query = [
            'submitted_by' => [
                'operator' => 'contains',
                'value' => [
                    $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    10000000
                ]
            ]
        ];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('/api/v1/trackers/' . $this->tracker_artifacts_tracker_id . '/artifacts?limit=100&query=' . urlencode(json_encode($query)))
        );

        $artifacts = $response->json();

        $this->assertCount(1, $artifacts);

        $artifact = $artifacts[0];

        $this->assertEquals(REST_TestDataBuilder::TEST_USER_1_NAME, $artifact['submitted_by_user']['username']);
    }

    public function testQueryFiltersOnSubmittedByFieldWithWrongUserIdReturnsEmptyCollection(): void
    {
        $query = [
            'submitted_by' => [
                'operator' => 'contains',
                'value' => 10000000
            ]
        ];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('/api/v1/trackers/' . $this->tracker_artifacts_tracker_id . '/artifacts?limit=100&query=' . urlencode(json_encode($query)))
        );

        $artifacts = $response->json();

        $this->assertCount(0, $artifacts);
    }

    public function testQueryFiltersOnSubmittedByFieldWithValidUserIdsReturnsArtifactsSubmittedByTheseUsers(): void
    {
        $query = [
            'submitted_by' => [
                'operator' => 'contains',
                'value' => [
                    $this->user_ids[REST_TestDataBuilder::TEST_USER_1_NAME],
                    $this->user_ids[DataBuilder::USER_TESTER_NAME]
                ]
            ]
        ];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('/api/v1/trackers/' . $this->tracker_artifacts_tracker_id . '/artifacts?limit=100&query=' . urlencode(json_encode($query)))
        );

        $artifacts = $response->json();

        $this->assertCount(2, $artifacts);

        foreach ($artifacts as $artifact) {
            $this->assertContains(
                $artifact['submitted_by_user']['username'],
                [
                    REST_TestDataBuilder::TEST_USER_1_NAME,
                    DataBuilder::USER_TESTER_NAME
                ]
            );
        }
    }
}
