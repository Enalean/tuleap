<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST;

require_once dirname(__FILE__) . '/../bootstrap.php';

class ExplicitBacklogTest extends TestBase
{
    public function testTopBacklogInExplicitBacklogContextIsEmptyWhileNoArtifactExplicitlyAdded(): void
    {
        $this->assertTopBacklogIsEmpty();
    }

    public function testPatchATopBacklogInExplicitContextDoesNotFail(): void
    {
        $artifact_id_to_add = $this->getFirstStoryArtifactId();
        $patch_body = json_encode([
            'add' => [
                ['id' => $artifact_id_to_add],
            ]
        ]);

        $response_patch = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch(
                'projects/' . urlencode((string) $this->explicit_backlog_project_id) . '/backlog',
                null,
                $patch_body
            )
        );

        $this->assertEquals(200, $response_patch->getStatusCode());
    }

    /**
     * @depends testPatchATopBacklogInExplicitContextDoesNotFail
     */
    public function testTopBacklogInExplicitBacklogContextContainsTheBacklogItemsAfterBeingAdded(): void
    {
        $this->assertTopBacklogContainsTheFirstStory();
        $this->assertReleaseIsEmpty();
    }

    /**
     * @depends testTopBacklogInExplicitBacklogContextContainsTheBacklogItemsAfterBeingAdded
     */
    public function testTopBacklogInExplicitBacklogContextDoesNotContainTheBacklogItemsMovedToTheRelease(): void
    {
        $this->moveStoryToRelease();

        $this->assertTopBacklogIsEmpty();
        $this->assertReleaseIsNotEmpty();
    }

    /**
     * @depends testTopBacklogInExplicitBacklogContextDoesNotContainTheBacklogItemsMovedToTheRelease
     */
    public function testTopBacklogInExplicitBacklogContextContainsTheBacklogItemsMovedFromTheRelease(): void
    {
        $this->moveStoryFromReleaseToTopBacklog();

        $this->assertTopBacklogContainsTheFirstStory();
        $this->assertReleaseIsEmpty();
    }

    /**
     * @depends testTopBacklogInExplicitBacklogContextContainsTheBacklogItemsMovedFromTheRelease
     */
    public function testTopBacklogInExplicitBacklogIsEmptyAfterRemovingTheStory(): void
    {
        $this->removeStoryFromTopBacklog();

        $this->assertTopBacklogIsEmpty();
        $this->assertReleaseIsEmpty();
    }

    private function moveStoryToRelease()
    {
        $artifact_id_to_add  = $this->getFirstStoryArtifactId();
        $release_artifact_id = $this->getFirstReleaseArtifactId();
        $patch_body          = json_encode([
            'add' => [
                ['id' => $artifact_id_to_add],
            ]
        ]);

        $response_patch = $this->getResponse(
            $this->client->patch(
                'milestones/' . urlencode((string) $release_artifact_id) . '/content',
                null,
                $patch_body
            )
        );

        $this->assertEquals(200, $response_patch->getStatusCode());
    }

    private function moveStoryFromReleaseToTopBacklog()
    {
        $artifact_id_to_add  = $this->getFirstStoryArtifactId();
        $release_artifact_id = $this->getFirstReleaseArtifactId();
        $patch_body          = json_encode([
            'add' => [
                [
                    'id'          => $artifact_id_to_add,
                    'remove_from' => $release_artifact_id
                ],
            ]
        ]);

        $response_patch = $this->getResponse(
            $this->client->patch(
                'projects/' . urlencode((string) $this->explicit_backlog_project_id) . '/backlog',
                null,
                $patch_body
            )
        );

        $this->assertEquals(200, $response_patch->getStatusCode());
    }

    private function removeStoryFromTopBacklog()
    {
        $artifact_id_to_add  = $this->getFirstStoryArtifactId();
        $patch_body          = json_encode([
            'remove' => [
                [
                    'id' => $artifact_id_to_add
                ],
            ]
        ]);

        $response_patch = $this->getResponse(
            $this->client->patch(
                'projects/' . urlencode((string) $this->explicit_backlog_project_id) . '/backlog',
                null,
                $patch_body
            )
        );

        $this->assertEquals(200, $response_patch->getStatusCode());
    }

    private function getFirstStoryArtifactId(): int
    {
        return (int) $this->explicit_backlog_artifact_story_ids[1];
    }

    private function getFirstReleaseArtifactId(): int
    {
        return (int) $this->explicit_backlog_artifact_release_ids[1];
    }

    private function assertTopBacklogContainsTheFirstStory(): void
    {
        $response          = $this->getResponse($this->client->get('projects/' . urlencode((string) $this->explicit_backlog_project_id) . '/backlog'));
        $top_backlog_items = $response->json();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertCount(1, $top_backlog_items);
        $this->assertSame($top_backlog_items[0]['id'], $this->getFirstStoryArtifactId());
    }

    private function assertTopBacklogIsEmpty(): void
    {
        $response          = $this->getResponse($this->client->get('projects/' . urlencode((string) $this->explicit_backlog_project_id) . '/backlog'));
        $top_backlog_items = $response->json();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEmpty($top_backlog_items);
    }

    private function assertReleaseIsNotEmpty(): void
    {
        $release_artifact_id = $this->getFirstReleaseArtifactId();

        $response      = $this->getResponse($this->client->get('milestones/' . urlencode((string) $release_artifact_id) . '/content'));
        $release_items = $response->json();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertCount(1, $release_items);
        $this->assertSame($release_items[0]['id'], $this->getFirstStoryArtifactId());
    }

    private function assertReleaseIsEmpty(): void
    {
        $release_artifact_id = $this->getFirstReleaseArtifactId();

        $response      = $this->getResponse($this->client->get('milestones/' . urlencode((string) $release_artifact_id) . '/content'));
        $release_items = $response->json();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEmpty($release_items);
    }
}
