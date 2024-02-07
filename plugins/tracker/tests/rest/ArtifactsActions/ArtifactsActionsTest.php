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

use Psr\Http\Message\ResponseInterface;
use REST_TestDataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

final class ArtifactsActionsTest extends TrackerBase
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

        self::assertMoveDryRun($response);
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

        self::assertEquals(403, $response->getStatusCode());
    }

    private function assertMoveDryRun(\Psr\Http\Message\ResponseInterface $response)
    {
        self::assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey("dry_run", $json);
        self::assertArrayHasKey("fields", $json['dry_run']);

        $migrated_fields = $json['dry_run']['fields']['fields_migrated'];
        self::assertCount(38, $migrated_fields);

        $not_migrated_fields = $json['dry_run']['fields']['fields_not_migrated'];
        self::assertCount(1, $not_migrated_fields);

        self::assertTrue($this->isFieldInArrayByLabel($not_migrated_fields, 'step exec'));

        $partially_migrated_fields = $json['dry_run']['fields']['fields_partially_migrated'];
        self::assertCount(0, $partially_migrated_fields);
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

        self::assertEquals(403, $response->getStatusCode());
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

        self::assertMoveArtifact($response, $artifact_id);

        $changeset_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id/changesets?fields=comments&limit=10")
        );

        self::assertMoveChangeset($changeset_response);
    }

    private function assertMoveArtifact(\Psr\Http\Message\ResponseInterface $response, $artifact_id): void
    {
        self::assertEquals(200, $response->getStatusCode());

        $artifact_response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id?values_format=all")
        );

        self::assertEquals($artifact_response->getStatusCode(), 200);
    }

    private function assertMoveChangeset(\Psr\Http\Message\ResponseInterface $changeset_response): void
    {
        self::assertEquals(200, $changeset_response->getStatusCode());
        $changeset_json = json_decode($changeset_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $changeset_json);
        self::assertEquals($changeset_json[0]['last_comment']['body'], "Artifact was moved from 'tracker source' tracker in 'Move artifact' project.");
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

        self::assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testMoveArtifact
     */
    public function testDeleteArtifacts(): void
    {
        $response = $this->performArtifactDeletion($this->delete_artifact_ids[1]);

        self::assertEquals(
            "1",
            $response->getHeader('x-ratelimit-limit')[0],
        );

        self::assertEquals(
            "0",
            $response->getHeader('x-ratelimit-remaining')[0],
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testDeleteArtifacts
     */
    public function testItThrowsAnErrorWhenUserReachesTheLimitOfDeletedArtifacts(): void
    {
        $response = $this->performArtifactDeletion($this->delete_artifact_ids[2]);

        self::assertEquals(429, $response->getStatusCode());
    }

    private function performArtifactDeletion($artifact_id, $user_name = REST_TestDataBuilder::TEST_USER_1_NAME): ResponseInterface
    {
        $url = "artifacts/$artifact_id";

        return $this->getResponse(
            $this->request_factory->createRequest('DELETE', $url),
            $user_name
        );
    }

    public function testMoveArtifactForbidden(): void
    {
        $artifact_id = end($this->move_forbidden_artifact_ids);
        $body        = json_encode(
            [
                "move" => [
                    "tracker_id" => $this->move_destination_tracker_id,
                    "dry_run" => true,
                ],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', "artifacts/$artifact_id")->withBody($this->stream_factory->createStream($body))
        );

        self::assertEquals(400, $response->getStatusCode());
    }
}
