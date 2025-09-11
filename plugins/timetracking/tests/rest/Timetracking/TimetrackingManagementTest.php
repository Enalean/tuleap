<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST;

use PHPUnit\Framework\Attributes\Depends;
use function Psl\Json\encode;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimetrackingManagementTest extends TimetrackingBase
{
    public function testOptionsUsers(): void
    {
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->request_factory->createRequest('OPTIONS', 'timetracking_management_users')
        );
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGetUsers(): void
    {
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->request_factory->createRequest('GET', 'timetracking_management_users?query=supercalifra')
        );
        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $json);

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->request_factory->createRequest('GET', 'timetracking_management_users?query=rest')
        );
        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Timetracking User 1', $json[0]['real_name']);
    }

    public function testGetManagementWidgetId(): int
    {
        $query = encode(['name' => 'My dashboard ' . microtime()]);

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->request_factory->createRequest('POST', 'user_dashboards')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $query    = encode(['dashboard_id' => $json['id'], 'dashboard_type' => 'user']);
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->request_factory->createRequest('POST', 'timetracking_management_widget')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $json['id'];
    }

    #[Depends('testGetManagementWidgetId')]
    public function testPutConfiguration(int $id): int
    {
        $query = encode([
            'start_date' => null,
            'end_date' => null,
            'predefined_time_period' => 'current_week',
            'users' => [
                [
                    'id' => $this->user_ids[TimetrackingDataBuilder::USER_TESTER_NAME],
                ],
            ],
        ]);

        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->request_factory->createRequest('PUT', "timetracking_management_widget/$id")->withBody($this->stream_factory->createStream($query))
        );
        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(TimetrackingDataBuilder::USER_TESTER_NAME, $json['viewable_users'][0]['username']);

        return $id;
    }

    #[Depends('testPutConfiguration')]
    public function testGetTimes(int $id): void
    {
        $response = $this->getResponseByName(
            TimetrackingDataBuilder::USER_TESTER_NAME,
            $this->request_factory->createRequest('GET', "timetracking_management_widget/$id/times")
        );
        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(TimetrackingDataBuilder::USER_TESTER_NAME, $json[0]['user']['username']);
        $this->assertSame(TimetrackingDataBuilder::PROJECT_TEST_TIMETRACKING_SHORTNAME, $json[0]['times'][0]['project']['shortname']);
    }
}
