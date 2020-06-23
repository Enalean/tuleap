<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TestPlan\REST\v1;

final class BacklogItemTest extends \RestBase
{
    public function testRetrievesLinkedTestDefinitionsToABacklogItem(): void
    {
        $backlog_item_id = $this->getIDOfTheOnlyArtifactOfTheTracker('Sprints');
        $milestone_id    = $this->getIDOfTheOnlyArtifactOfTheTracker('Releases');

        $response = $this->getResponse(
            $this->client->get('backlog_items/' . urlencode((string) $backlog_item_id) . '/test_definitions?milestone_id=' . urlencode((string) $milestone_id))
        );
        $this->assertEquals(200, $response->getStatusCode());
        $linked_test_definitions = $response->json();

        $this->assertCount(1, $linked_test_definitions);

        $linked_test_definition = $linked_test_definitions[0];
        $this->assertEquals('Expected Test Def 1', $linked_test_definition['summary']);
        $this->assertEquals('test_case', $linked_test_definition['short_type']);
        $this->assertEquals('passed', $linked_test_definition['test_status']);
    }

    private function getIDOfTheOnlyArtifactOfTheTracker(string $tracker_label): int
    {
        $tracker_id = $this->getTestPlanTrackerID($tracker_label);

        $response = $this->getResponse($this->client->get('trackers/' . urlencode((string) $tracker_id) . '/artifacts'));
        $this->assertEquals(200, $response->getStatusCode());
        $artifacts = $response->json();
        $this->assertCount(1, $artifacts);

        return $artifacts[0]['id'];
    }

    private function getTestPlanTrackerID(string $tracker_label): int
    {
        $project_id = $this->getProjectId('testplan');
        $response   = $this->getResponse($this->client->get('projects/' . urlencode((string) $project_id) . '/trackers?representation=minimal'));
        $this->assertEquals(200, $response->getStatusCode());

        $trackers   = $response->json();
        $tracker_id = null;
        foreach ($trackers as $tracker) {
            if ($tracker['label'] === $tracker_label) {
                $tracker_id = $tracker['id'];
            }
        }

        $this->assertNotNull($tracker_id);
        return $tracker_id;
    }
}
