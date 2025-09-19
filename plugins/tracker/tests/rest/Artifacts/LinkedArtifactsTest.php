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

use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkedArtifactsTest extends TrackerBase
{
    private const PROJECT_SHORTNAME = 'linked-artifacts';
    private const TRACKER_SHORTNAME = 'linked_artifacts';
    private const FIELD_SHORTNAME   = 'link';
    private int $status_value_id    = 0;


    public function testLinkedArtifacts(): void
    {
        $project_id = $this->getProjectId(self::PROJECT_SHORTNAME);
        $tracker_id = $this->tracker_ids[$project_id][self::TRACKER_SHORTNAME];
        $field_id   = $this->getAUsedFieldId($tracker_id, self::FIELD_SHORTNAME);

        $tracker = $this->tracker_representations[$tracker_id];
        foreach ($tracker['fields'] as $field) {
            if ($field['name'] === 'status') {
                $status_field_id       = $field['field_id'];
                $this->status_value_id = $field['values'][0]['id'];
            }
        }

        $artifact_id_1 = $this->createArtifact($tracker_id, $field_id, $status_field_id);
        $artifact_id_2 = $this->createArtifact($tracker_id, $field_id, $status_field_id);
        $artifact_id_3 = $this->createArtifact($tracker_id, $field_id, $status_field_id, $artifact_id_1, $artifact_id_2);

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $artifact_id_3) . '/linked_artifacts?direction=forward')
        );
        $this->assertArtifactLinks($response, $artifact_id_1, $artifact_id_2);

        $response_with_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $artifact_id_3) . '/linked_artifacts?direction=forward'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertArtifactLinks($response_with_read_only_user, $artifact_id_1, $artifact_id_2);

        $response_flat = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $artifact_id_3) . '/linked_artifacts?direction=forward&output_format=flat')
        );
        $this->assertSame(
            [['status' => ['To be done']], ['status' => ['To be done']]],
            \Psl\Json\decode($response_flat->getBody()->getContents())
        );

        $response_really_flat = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $artifact_id_3) . '/linked_artifacts?direction=forward&output_format=flat_with_semicolon_string_array')
        );
        $this->assertSame(
            [['status' => 'To be done'], ['status' => 'To be done']],
            \Psl\Json\decode($response_really_flat->getBody()->getContents())
        );
    }

    public function testPUTReverseArtifacts(): void
    {
        $project_id = $this->getProjectId(self::PROJECT_SHORTNAME);
        $tracker_id = $this->tracker_ids[$project_id][self::TRACKER_SHORTNAME];
        $field_id   = $this->getAUsedFieldId($tracker_id, self::FIELD_SHORTNAME);

        $tracker = $this->tracker_representations[$tracker_id];
        foreach ($tracker['fields'] as $field) {
            if ($field['name'] === 'status') {
                $status_field_id       = $field['field_id'];
                $this->status_value_id = $field['values'][0]['id'];
            }
        }

        $artifact_id_1 = $this->createArtifact($tracker_id, $field_id, $status_field_id);
        $artifact_id_2 = $this->createArtifact($tracker_id, $field_id, $status_field_id);
        $artifact_id_3 = $this->createArtifact($tracker_id, $field_id, $status_field_id);

        $body = json_encode(
            [
                'values' => [
                    [
                        'field_id' => $field_id,
                        'all_links' => [
                            [
                                'id'        => $artifact_id_2,
                                'direction' => LinkDirection::REVERSE->value,
                                'type'      => ArtifactLinkField::DEFAULT_LINK_TYPE,
                            ],
                            [
                                'id'        => $artifact_id_3,
                                'direction' => LinkDirection::REVERSE->value,
                                'type'      => ArtifactLinkField::DEFAULT_LINK_TYPE,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponseByName(
            RESTTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', "artifacts/$artifact_id_1")->withBody($this->stream_factory->createStream($body))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertReverseLinkExist($artifact_id_2, $artifact_id_1, null);
        $this->assertReverseLinkExist($artifact_id_3, $artifact_id_1, null);

        $body = json_encode(
            [
                'values' => [
                    [
                        'field_id'  => $field_id,
                        'all_links' => [
                            [
                                'id'        => $artifact_id_2,
                                'direction' => LinkDirection::REVERSE->value,
                                'type'      => ArtifactLinkField::TYPE_IS_CHILD,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->getResponseByName(
            RESTTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', "artifacts/$artifact_id_1")->withBody($this->stream_factory->createStream($body))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertReverseLinkExist($artifact_id_2, $artifact_id_1, ArtifactLinkField::TYPE_IS_CHILD);

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $artifact_id_3) . '/linked_artifacts?direction=forward')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $linked_artifacts_collection = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEmpty($linked_artifacts_collection['collection']);
    }

    public function testPOSTArtifacts(): void
    {
        $project_id = $this->getProjectId(self::PROJECT_SHORTNAME);
        $tracker_id = $this->tracker_ids[$project_id][self::TRACKER_SHORTNAME];
        $field_id   = $this->getAUsedFieldId($tracker_id, self::FIELD_SHORTNAME);

        $tracker = $this->tracker_representations[$tracker_id];
        foreach ($tracker['fields'] as $field) {
            if ($field['name'] === 'status') {
                $status_field_id       = $field['field_id'];
                $this->status_value_id = $field['values'][0]['id'];
            }
        }

        $artifact_id_1 = $this->createArtifact($tracker_id, $field_id, $status_field_id);
        $artifact_id_2 = $this->createArtifact($tracker_id, $field_id, $status_field_id);

        $body = json_encode(
            [
                'tracker' => [
                    'id' => $tracker_id,
                ],
                'values' => [
                    [
                        'field_id' => $field_id,
                        'all_links' => [
                            [
                                'id' => $artifact_id_1,
                                'direction' => LinkDirection::FORWARD->value,
                                'type' => ArtifactLinkField::DEFAULT_LINK_TYPE,
                            ],
                            [
                                'id' => $artifact_id_2,
                                'direction' => LinkDirection::REVERSE->value,
                                'type' => ArtifactLinkField::DEFAULT_LINK_TYPE,
                            ],
                        ],
                    ],
                    [
                        'field_id' => $status_field_id,
                        'bind_value_ids' => [$this->status_value_id],
                    ],
                ],
            ]
        );

        $response = $this->getResponseByName(
            RESTTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream($body))
        );

        $this->assertEquals(201, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertReverseLinkExist($json['id'], $artifact_id_1, ArtifactLinkField::DEFAULT_LINK_TYPE);
        $this->assertReverseLinkExist($artifact_id_2, $json['id'], ArtifactLinkField::DEFAULT_LINK_TYPE);
    }

    private function assertReverseLinkExist(int $source_artifact_id, int $artifact_id, ?string $expected_type): void
    {
        $type = $expected_type ? urlencode($expected_type) : '';

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $source_artifact_id) . '/linked_artifacts?nature=' . $type . '&direction=forward')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $linked_artifacts_collection = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotEmpty($linked_artifacts_collection['collection']);
        $this->assertEquals($artifact_id, $linked_artifacts_collection['collection'][0]['id']);
    }

    private function createArtifact(int $tracker_id, int $art_link_field_id, int $status_field_id, int ...$linked_artifacts): int
    {
        $payload = [
            'tracker' => ['id' => $tracker_id],
            'values'  => [
                [
                    'field_id' => $art_link_field_id,
                    'links'    => (static function (int ...$linked_artifacts): array {
                        $representations = [];

                        foreach ($linked_artifacts as $linked_artifact) {
                            $representations[] = ['id' => $linked_artifact];
                        }

                        return $representations;
                    })(...$linked_artifacts),
                ],
                [
                    'field_id' => $status_field_id,
                    'bind_value_ids' => [$this->status_value_id],
                ],
            ],
        ];

        $response_with_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream(json_encode($payload))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_read_only_user->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream(json_encode($payload)))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $json['id'];
    }

    private function assertArtifactLinks(\Psr\Http\Message\ResponseInterface $response, int $artifact_id_1, int $artifact_id_2): void
    {
        $this->assertEquals(200, $response->getStatusCode());

        $linked_artifacts_collection = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $linked_artifacts_id     = [];
        $linked_artifacts_status = [];
        foreach ($linked_artifacts_collection['collection'] as $linked_artifact) {
            $linked_artifacts_id[]     = $linked_artifact['id'];
            $linked_artifacts_status[] = $linked_artifact['status'];
        }

        $this->assertEqualsCanonicalizing([$artifact_id_1, $artifact_id_2], $linked_artifacts_id);
        $this->assertEqualsCanonicalizing(['To be done', 'To be done'], $linked_artifacts_status);
    }
}
