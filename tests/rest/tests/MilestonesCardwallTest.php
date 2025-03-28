<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

use Tuleap\REST\MilestoneBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('MilestonesTest')]
class MilestonesCardwallTest extends MilestoneBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testOPTIONSCardwallOnSprintGivesOPTIONSandGET(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'milestones/' . $this->sprint_artifact_ids[1] . '/cardwall'));
        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSCardwallWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'milestones/' . $this->sprint_artifact_ids[1] . '/cardwall'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETCardwall(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->sprint_artifact_ids[1] . '/cardwall'));

        $this->assertCardwall($response);
    }

    public function testGETCardwallWithRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'milestones/' . $this->sprint_artifact_ids[1] . '/cardwall'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertCardwall($response);
    }

    private function assertCardwall(\Psr\Http\Message\ResponseInterface $response): void
    {
        $cardwall = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('columns', $cardwall);
        $columns = $cardwall['columns'];
        $this->assertCount(4, $columns);

        $first_column = $columns[0];
        $this->assertEquals($first_column['id'], 1);
        $this->assertEquals($first_column['label'], 'To be done');
        $this->assertEquals($first_column['color'], '#F8F8F8');

        $third_column = $columns[2];
        $this->assertEquals($third_column['id'], 3);
        $this->assertEquals($third_column['label'], 'Review');
        $this->assertEquals($third_column['color'], '#F8F8F8');

        $this->assertArrayHasKey('swimlanes', $cardwall);
        $swimlanes = $cardwall['swimlanes'];
        $this->assertCount(2, $swimlanes);

        $first_swimlane = $swimlanes[0];

        $first_swimlane_card = $first_swimlane['cards'][0];
        $this->assertEquals(REST_TestDataBuilder::PLANNING_ID . '_' . $this->story_artifact_ids[1], $first_swimlane_card['id']);
        $this->assertEquals('Believe', $first_swimlane_card['label']);
        $this->assertEquals('cards/' . REST_TestDataBuilder::PLANNING_ID . '_' . $this->story_artifact_ids[1], $first_swimlane_card['uri']);
        $this->assertEquals(REST_TestDataBuilder::PLANNING_ID, $first_swimlane_card['planning_id']);
        $this->assertEquals('Open', $first_swimlane_card['status']);
        $this->assertEquals(null, $first_swimlane_card['accent_color']);
        $this->assertEquals('2', $first_swimlane_card['column_id']);
        $this->assertEquals([1, 2, 4], $first_swimlane_card['allowed_column_ids']);
        $this->assertEquals([], $first_swimlane_card['values']);

        $first_swimlane_card_project_reference = $first_swimlane_card['project'];
        $this->assertEquals($this->project_private_member_id, $first_swimlane_card_project_reference['id']);
        $this->assertEquals("projects/$this->project_private_member_id", $first_swimlane_card_project_reference['uri']);

        $first_swimlane_card_artifact_reference = $first_swimlane_card['artifact'];
        $this->assertEquals($this->story_artifact_ids[1], $first_swimlane_card_artifact_reference['id']);
        $this->assertEquals('artifacts/' . $this->story_artifact_ids[1], $first_swimlane_card_artifact_reference['uri']);

        $first_swimlane_card_artifact_tracker_reference = $first_swimlane_card_artifact_reference['tracker'];
        $this->assertEquals($this->user_stories_tracker_id, $first_swimlane_card_artifact_tracker_reference['id']);
        $this->assertEquals('trackers/' . $this->user_stories_tracker_id, $first_swimlane_card_artifact_tracker_reference['uri']);
    }
}
