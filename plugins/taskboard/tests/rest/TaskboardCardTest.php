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

class TaskboardCardTest extends RestBase
{
    /**
     * @var int
     */
    private static $user_story_6_id;
    /**
     * @var int
     */
    private static $milestone_id;

    public function setUp(): void
    {
        parent::setUp();
        if (! self::$user_story_6_id) {
            self::$milestone_id    = $this->getMilestoneId();
            self::$user_story_6_id = $this->getUserStory6Id();
        }
    }

    /**
     * @dataProvider getUserName
     */
    public function testOPTIONSChildren(string $user_name): void
    {
        $response = $this->getResponse(
            $this->client->options('taskboard_cards/' . self::$user_story_6_id . '/children'),
            $user_name
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @dataProvider getUserName
     */
    public function testGETChildren(string $user_name): void
    {
        $response = $this->getResponse(
            $this->client->get('taskboard_cards/' . self::$user_story_6_id . '/children?milestone_id=' . self::$milestone_id),
            $user_name
        );
        $this->assertEquals(200, $response->getStatusCode());

        $cards = $response->json();
        $this->assertCount(1, $cards);
        $this->assertEquals('AA', $cards[0]['label']);
        $this->assertStringMatchesFormat('tasks #%i', $cards[0]['xref']);
    }

    /**
     * @dataProvider getUserName
     */
    public function testGETNoChildrenWhenNoMilestoneGiven(string $user_name): void
    {
        $response = $this->getResponse(
            $this->client->get('taskboard_cards/' . self::$user_story_6_id . '/children'),
            $user_name
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @dataProvider getUserName
     */
    public function testGETNoChildrenWhenWrongMilestoneGiven(string $user_name): void
    {
        $response = $this->getResponse(
            $this->client->get('taskboard_cards/' . self::$user_story_6_id . '/children?milestone_id=0'),
            $user_name
        );
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @dataProvider getUserName
     */
    public function testOPTIONSId(string $user_name): void
    {
        $response = $this->getResponse(
            $this->client->options('taskboard_cards/' . self::$user_story_6_id),
            $user_name
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['OPTIONS', 'GET', 'PATCH'], $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @dataProvider getUserName
     */
    public function testGetId(string $user_name): void
    {
        $response = $this->getResponse(
            $this->client->get('taskboard_cards/' . self::$user_story_6_id . '?milestone_id=' . self::$milestone_id),
            $user_name
        );

        $this->assertEquals(200, $response->getStatusCode());

        $card = $response->json();
        $this->assertEquals('US6', $card['label']);
    }

    public function testGetIdFailsWhenNoMilestoneGiven(): void
    {
        $response = $this->getResponse(
            $this->client->get('taskboard_cards/' . self::$user_story_6_id),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetIdFailsWhenWrongMilestoneGiven(): void
    {
        $response = $this->getResponse(
            $this->client->get('taskboard_cards/' . self::$user_story_6_id . '?milestone_id=0'),
            REST_TestDataBuilder::TEST_USER_1_NAME
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

    private function getUserStory6Id(): int
    {
        $response = $this->getResponse($this->client->get('taskboard/' . self::$milestone_id . '/cards'));
        $this->assertEquals(200, $response->getStatusCode());
        $cards = $response->json();
        foreach ($cards as $card) {
            if ($card['label'] === 'US6') {
                return (int) $card['id'];
            }
        }

        return 0;
    }
}
