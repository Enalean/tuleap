<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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
 *
 */

namespace Test\Rest\Regression;

use RestBase;
use Test\Rest\Tracker\TrackerFactory;
use REST_TestDataBuilder;

/**
 * Corresponds to request #10465 Not able to add an artifact link
 *
 * Given there are 2 trackers with hierarchy (Epic father of Story)
 * And the parent Epic has a PermissionOnArtifact field required
 * When a story is linked to the epic
 * Then the epic should be updated with a link '_is_child' toward the story
 *
 * @group Regressions
 */
class PermissionOnArtifactRequiredTest extends RestBase
{
    const PROJECT_NAME = 'hierarchy-tests';

    public function testItsLinkedToParentAsChild()
    {
        $epic_id       = $this->getParentEpic('foo');
        $created_story = $this->createChildStory($epic_id);
        $links         = $this->getArtifactLinkFieldValue($epic_id);

        $this->assertNotNull($links);
        $this->assertCount(1, $links);
        $this->assertEquals($created_story['id'], $links[0]['id']);
    }

    private function getParentEpic(string $title)
    {
        return $this->getArtifactIdsIndexedByTitle(self::PROJECT_NAME, 'epic')[$title];
    }

    private function createChildStory(int $epic_id)
    {
        $tracker_test_helper = new TrackerFactory(
            $this->client,
            $this->rest_request,
            $this->getProjectId(self::PROJECT_NAME),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $story_tracker = $tracker_test_helper->getTrackerRest('story');

        return $story_tracker->createArtifact([
            $story_tracker->getSubmitArtifactLinkValue([$epic_id])
        ]);
    }

    private function getArtifactLinkFieldValue(int $epic_id)
    {
        $request = $this->client->get("artifacts/$epic_id");
        $epic_updated = $this->getResponse($request)->json();

        $links = null;
        foreach ($epic_updated['values'] as $field) {
            if ($field['type'] === 'art_link') {
                return $field['links'];
            }
        }
    }
}
