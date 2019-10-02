<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST;

use REST_TestDataBuilder;
use RestBase;

class TaskboardTest extends RestBase
{
    /**
     * @var int
     */
    private static $milestone_id;

    public function setUp(): void
    {
        parent::setUp();
        if (! self::$milestone_id) {
            self::$milestone_id = $this->getMilestoneId();
        }
    }

    /**
     * @dataProvider getUserName
     */
    public function testOPTIONSCards(string $user_name): void
    {
        $response = $this->getResponse(
            $this->client->options('taskboard/' . self::$milestone_id . '/cards'),
            $user_name
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @dataProvider getUserName
     */
    public function testGETCards(string $user_name): void
    {
        $response = $this->getResponse(
            $this->client->get('taskboard/' . self::$milestone_id . '/cards'),
            $user_name
        );
        $this->assertEquals(200, $response->getStatusCode());

        $cards = $response->json();
        $this->assertCount(6, $cards);
        foreach (['US1', 'US2', 'Us3', 'US4', 'US5', 'US6'] as $key => $label) {
            $this->assertNotEmpty($cards[$key]['id']);
            $this->assertEquals($label, $cards[$key]['label']);
            $this->assertStringMatchesFormat('story #%i', $cards[$key]['xref']);
            $this->assertNotEmpty($cards[$key]['rank']);
            $this->assertEquals('lake-placid-blue', $cards[$key]['color']);
            $this->assertEquals('/plugins/tracker/?aid='. $cards[$key]['id'], $cards[$key]['artifact_html_uri']);
            $expected_background_color = $label === 'US2' ? 'fiesta-red' : '';
            $this->assertEquals($expected_background_color, $cards[$key]['background_color']);
            $expected_has_children = $label === 'US6';
            $this->assertEquals($expected_has_children, $cards[$key]['has_children']);
            $this->assertArrayHasKey('initial_effort', $cards[$key]);
            $this->assertArrayHasKey('assignees', $cards[$key]);
            $this->assertArrayHasKey('remaining_effort', $cards[$key]);

            if ($label === 'US1') {
                $this->assertNotEmpty($cards[$key]['assignees']);
                $this->assertEquals($cards[$key]['assignees'][0]['username'], 'rest_api_tester_1');
            }
        }
    }

    /**
     * @dataProvider getUserName
     */
    public function testGETNoMilestone(string $user_name): void
    {
        $response = $this->getResponse(
            $this->client->get('taskboard/0/cards'),
            $user_name
        );
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function getUserName(): array
    {
        return [
            [REST_TestDataBuilder::TEST_USER_1_NAME],
            [REST_TestDataBuilder::TEST_BOT_USER_NAME]
        ];
    }

    private function getMilestoneId(): int
    {
        $project_id = $this->getProjectId('taskboard');

        $response   = $this->getResponse($this->client->get('projects/' . $project_id . '/milestones'));
        $milestones = $response->json();

        $this->assertCount(1, $milestones);

        return (int) $milestones[0]['id'];
    }
}
