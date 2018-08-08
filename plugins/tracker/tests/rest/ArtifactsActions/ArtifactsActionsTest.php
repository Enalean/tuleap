<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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

namespace Tuleap\Tracker\Tests\REST\ArtifactsActions;

use REST_TestDataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__.'/../bootstrap.php';

class ArtifactsActionsTest extends TrackerBase
{
    public function testMoveArtifactDryRun()
    {
        $artifact_id = end($this->base_artifact_ids);
        $body        = json_encode(
            [
                "move" => [
                    "tracker_id" => $this->move_tracker_id,
                    "dry_run"    => true,
                ]
            ]
        );

        $response = $this->getResponse(
            $this->client->patch("artifacts/$artifact_id", null, $body)
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            $response->getHeader('x-ratelimit-limit')->toArray()[0],
            "2"
        );

        $this->assertEquals(
            $response->getHeader('x-ratelimit-remaining')->toArray()[0],
            "2"
        );

        $json = $response->json();

        $this->assertArrayHasKey("dry_run", $json);
        $this->assertArrayHasKey("fields", $json['dry_run']);

        $migrated_fields = $json['dry_run']['fields']['fields_migrated'];
        $this->assertCount(5, $migrated_fields);

        $this->assertTrue($this->isFieldInArrayByLabel($migrated_fields, 'Summary'));
        $this->assertTrue($this->isFieldInArrayByLabel($migrated_fields, 'Description'));
        $this->assertTrue($this->isFieldInArrayByLabel($migrated_fields, 'Assigned to'));
        $this->assertTrue($this->isFieldInArrayByLabel($migrated_fields, 'Status'));
        $this->assertTrue($this->isFieldInArrayByLabel($migrated_fields, 'Initial'));

        $this->assertCount(0, $json['dry_run']['fields']['fields_not_migrated']);
        $this->assertCount(0, $json['dry_run']['fields']['fields_partially_migrated']);
    }

    private function isFieldInArrayByLabel(array $fields, $label)
    {
        foreach ($fields as $minimal_field_representation) {
            if ($minimal_field_representation['label'] === $label) {
                return true;
            }
        }

        return false;
    }

    /**
     * @depends testMoveArtifactDryRun
     */
    public function testMoveArtifact()
    {
        $artifact_id = end($this->base_artifact_ids);
        $body        = json_encode(
            [
                "move" => [
                    "tracker_id" => $this->move_tracker_id
                ]
            ]
        );

        $response = $this->getResponse(
            $this->client->patch("artifacts/$artifact_id", null, $body)
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            $response->getHeader('x-ratelimit-limit')->toArray()[0],
            "2"
        );

        $this->assertEquals(
            $response->getHeader('x-ratelimit-remaining')->toArray()[0],
            "1"
        );

        $artifact_response = $this->getResponse(
            $this->client->get("artifacts/$artifact_id?values_format=all")
        );

        $this->assertEquals($artifact_response->getStatusCode(), 200);
        $artifact_json = $artifact_response->json();

        $this->assertEquals($artifact_json['submitted_by_user']['username'], REST_TestDataBuilder::TEST_USER_2_NAME);
        $this->assertEquals($artifact_json['tracker']['id'], $this->move_tracker_id);
        $this->assertEquals($artifact_json['values_by_field']['title']['value'], "To be moved v2");
        $this->assertEquals($artifact_json['values_by_field']['desc']['value'], "Artifact that will be moved in another tracker");
        $this->assertEquals($artifact_json['values_by_field']['initialv2']['manual_value'], 25);
        $this->assertEquals($artifact_json['status'], 'On going');
        $this->assertEquals(count($artifact_json['assignees']), 1);
        $this->assertEquals((int) $artifact_json['assignees'][0]["id"], REST_TestDataBuilder::TEST_USER_3_ID);

        $changeset_response = $this->getResponse(
            $this->client->get("artifacts/$artifact_id/changesets?fields=comments&limit=5")
        );

        $this->assertEquals($changeset_response->getStatusCode(), 200);
        $changeset_json = $changeset_response->json();

        $this->assertEquals(count($changeset_json), 3);
        $this->assertEquals($changeset_json[0]['last_comment']['body'], "API 1 comment");
        $this->assertEquals($changeset_json[1]['last_comment']['body'], "API 2 comment");
        $this->assertEquals($changeset_json[2]['last_comment']['body'], "Artifact was moved from 'Base' tracker in 'Move artifact' project.");
    }

    /**
     * @depends testMoveArtifact
     */
    public function testDeleteArtifacts()
    {
        $response = $this->performArtifactDeletion($this->delete_artifact_ids[1]);

        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            $response->getHeader('x-ratelimit-limit')->toArray()[0],
            "2"
        );

        $this->assertEquals(
            $response->getHeader('x-ratelimit-remaining')->toArray()[0],
            "0"
        );
    }

    /**
     * @depends testDeleteArtifacts
     */
    public function itThrowsAnErrorWhenUserReachesTheLimitOfDeletedArtifacts()
    {
        $this->expectExceptionCode(429);
        $this->expectExceptionMessage('Too many requests: The limit of artifacts deletions has been reached for the previous 24 hours.');

        $this->performArtifactDeletion($this->delete_artifact_ids[2]);
    }

    private function performArtifactDeletion($artifact_id)
    {
        $url = "artifacts/$artifact_id";

        return $this->getResponse(
            $this->client->delete($url)
        );
    }
}
