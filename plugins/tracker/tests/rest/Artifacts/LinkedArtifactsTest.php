<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tests\REST\Artifacts;

use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

final class LinkedArtifactsTest extends TrackerBase
{
    private const PROJECT_SHORTNAME = 'linked-artifacts';
    private const TRACKER_SHORTNAME = 'linked_artifacts';
    private const FIELD_SHORTNAME   = 'link';

    public function testLinkedArtifacts() : void
    {
        $project_id = $this->getProjectId(self::PROJECT_SHORTNAME);
        $tracker_id = $this->tracker_ids[$project_id][self::TRACKER_SHORTNAME];
        $field_id   = $this->getAUsedFieldId($tracker_id, self::FIELD_SHORTNAME);

        $artifact_id_1 = $this->createArtifact($tracker_id, $field_id);
        $artifact_id_2 = $this->createArtifact($tracker_id, $field_id);
        $artifact_id_3 = $this->createArtifact($tracker_id, $field_id, $artifact_id_1, $artifact_id_2);

        $response = $this->getResponse(
            $this->client->get('artifacts/' . urlencode((string) $artifact_id_3) . '/linked_artifacts?direction=forward')
        );
        $this->assertArtifactLinks($response, $artifact_id_1, $artifact_id_2);

        $response_with_read_only_user = $this->getResponse(
            $this->client->get('artifacts/' . urlencode((string) $artifact_id_3) . '/linked_artifacts?direction=forward'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertArtifactLinks($response_with_read_only_user, $artifact_id_1, $artifact_id_2);
    }

    private function createArtifact(int $tracker_id, int $art_link_field_id, int ...$linked_artifacts) : int
    {
        $payload = [
            'tracker' => ['id' => $tracker_id],
            'values'  => [
                [
                    'field_id' => $art_link_field_id,
                    'links' => (static function (int ...$linked_artifacts) : array {
                        $representations = [];

                        foreach ($linked_artifacts as $linked_artifact) {
                            $representations[] = ['id' => $linked_artifact];
                        }

                        return $representations;
                    })(...$linked_artifacts)
                ]
            ]
        ];

        $response_with_read_only_user = $this->getResponse(
            $this->client->post('artifacts', null, json_encode($payload)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_read_only_user->getStatusCode());

        $response = $this->getResponse(
            $this->client->post('artifacts', null, json_encode($payload))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $json = $response->json();

        return $json['id'];
    }

    private function assertArtifactLinks(Response $response, int $artifact_id_1, int $artifact_id_2): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $linked_artifacts_collection = $response->json();

        $linked_artifacts_id = [];
        foreach ($linked_artifacts_collection['collection'] as $linked_artifact) {
            $linked_artifacts_id[] = $linked_artifact['id'];
        }

        $this->assertEqualsCanonicalizing([$artifact_id_1, $artifact_id_2], $linked_artifacts_id);
    }
}
