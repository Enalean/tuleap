<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
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

require_once __DIR__ . '/../bootstrap.php';

class ArtifactsActionsTest extends TrackerBase
{
    public function testMoveArtifactDryRun(): void
    {
        $artifact_id = end($this->base_artifact_ids);
        $body        = json_encode(
            [
                "move" => [
                    "tracker_id" => $this->move_tracker_id,
                    "dry_run" => true,
                ],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', "artifacts/$artifact_id")->withBody($this->stream_factory->createStream($body))
        );

        $this->assertMoveDryRun($response);
    }

    public function testMoveArtifactDryRunWithUserRESTReadOnlyAdminNotInProject()
    {
        $artifact_id = end($this->base_artifact_ids);
        $body        = json_encode(
            [
                "move" => [
                    "tracker_id" => $this->move_tracker_id,
                    "dry_run"    => true,
                ],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', "artifacts/$artifact_id")->withBody($this->stream_factory->createStream($body)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    private function assertMoveDryRun(\Psr\Http\Message\ResponseInterface $response)
    {
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            "2",
            $response->getHeader('x-ratelimit-limit')[0]
        );

        $this->assertEquals(
            "2",
            $response->getHeader('x-ratelimit-remaining')[0]
        );

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey("dry_run", $json);
        $this->assertArrayHasKey("fields", $json['dry_run']);

        $migrated_fields = $json['dry_run']['fields']['fields_migrated'];
        $this->assertCount(38, $migrated_fields);

        $not_migrated_fields = $json['dry_run']['fields']['fields_not_migrated'];
        $this->assertCount(1, $not_migrated_fields);

        $this->assertTrue($this->isFieldInArrayByLabel($not_migrated_fields, 'step exec'));

        $partially_migrated_fields = $json['dry_run']['fields']['fields_partially_migrated'];
        $this->assertCount(0, $partially_migrated_fields);
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
    public function testMoveArtifactWithUserRESTReadOnlyAdminNotInProject(): void
    {
        $artifact_id = end($this->base_artifact_ids);
        $body        = json_encode(
            [
                "move" => [
                    "tracker_id" => $this->move_tracker_id,
                ],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', "artifacts/$artifact_id")->withBody($this->stream_factory->createStream($body)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testMoveArtifactDryRun
     */
    public function testMoveArtifact(): void
    {
        $artifact_id = end($this->base_artifact_ids);
        $body        = json_encode(
            [
                "move" => [
                    "tracker_id" => $this->move_tracker_id,
                ],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', "artifacts/$artifact_id")->withBody($this->stream_factory->createStream($body))
        );

        $this->assertMoveArtifact($response, $artifact_id);

        $changeset_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id/changesets?fields=comments&limit=10")
        );

        $this->assertMoveChangeset($changeset_response);
    }

    private function assertMoveArtifact(\Psr\Http\Message\ResponseInterface $response, $artifact_id): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            "2",
            $response->getHeader('x-ratelimit-limit')[0]
        );

        $this->assertEquals(
            "1",
            $response->getHeader('x-ratelimit-remaining')[0]
        );

        $artifact_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id?values_format=all")
        );

        $this->assertEquals($artifact_response->getStatusCode(), 200);
    }

    private function assertMoveChangeset(\Psr\Http\Message\ResponseInterface $changeset_response): void
    {
        $this->assertEquals(200, $changeset_response->getStatusCode());
        $changeset_json = json_decode($changeset_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $changeset_json);
        $this->assertEquals($changeset_json[0]['last_comment']['body'], "Artifact was moved from 'tracker source' tracker in 'Move artifact' project.");
    }

    /**
     * @depends testMoveArtifact
     */
    public function testDeleteArtifactsWithUserRESTReadOnlyAdminNotInProject(): void
    {
        $response = $this->performArtifactDeletion(
            $this->delete_artifact_ids[1],
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testMoveArtifact
     */
    public function testDeleteArtifacts()
    {
        $response = $this->performArtifactDeletion($this->delete_artifact_ids[1]);

        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            $response->getHeader('x-ratelimit-limit')[0],
            "2"
        );

        $this->assertEquals(
            $response->getHeader('x-ratelimit-remaining')[0],
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

    private function performArtifactDeletion($artifact_id, $user_name = REST_TestDataBuilder::TEST_USER_1_NAME)
    {
        $url = "artifacts/$artifact_id";

        return $this->getResponse(
            $this->request_factory->createRequest('DELETE', $url),
            $user_name
        );
    }
}
