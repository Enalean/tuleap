<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TestManagement;


require_once __DIR__ . '/../bootstrap.php';

final class ArtifactsTest extends BaseTest
{
    public function testItPostAnArtifactWithStepDefinitionFieldId(): void
    {
        $test_def_tracker_id = $this->tracker_ids[$this->project_id][TestManagementDataBuilder::TEST_DEF_TRACKER_SHORTNAME];
        $summary_field_id    = $this->getUsedFieldId($test_def_tracker_id, "summary");
        $details_field_id    = $this->getUsedFieldId($test_def_tracker_id, "details");
        $steps_field_id      = $this->getUsedFieldId($test_def_tracker_id, "steps");

        $payload = [
            "tracker" => ["id" => $test_def_tracker_id],
            "values"  => [
                [
                    "field_id" => $summary_field_id,
                    "value"    => "Test if the car is usable or not"
                ],
                [
                    "field_id" => $details_field_id,
                    "value"    => "Cars go brrrrr"
                ],
                [
                    "field_id" => $steps_field_id,
                    "value"    => [
                        [
                            "description"             => "You can turn the steering wheel",
                            "description_format"      => "commonmark",
                            "expected_results"        => "The car should also turn ",
                            "expected_results_format" => "commonmark"
                        ],
                        [
                            "description"             => "The car does not have tyre",
                            "description_format"      => "html",
                            "expected_results"        => "It should be difficult to move",
                            "expected_results_format" => "html"
                        ]
                    ],
                ]
            ]
        ];

        $response = $this->getResponse(
            $this->client->post('artifacts', null, json_encode($payload))
        );

        self::assertEquals(201, $response->getStatusCode());

        $artifact_id = $response->json()["id"];
        $this->assertArtifactHasStepDefField($artifact_id);
    }

    public function testItPostAnArtifactWithStepDefinitionFieldName(): void
    {
        $test_def_tracker_id = $this->tracker_ids[$this->project_id][TestManagementDataBuilder::TEST_DEF_TRACKER_SHORTNAME];

        $payload = [
            "tracker"         => ["id" => $test_def_tracker_id],
            "values_by_field" => [
                "summary" => ["value" => "Test if the car is usable or not"],
                "details" => ["value" => "Cars go brrrrr"],
                "steps"   => [
                    "value" => [
                        [
                            "description"             => "You can turn the steering wheel",
                            "description_format"      => "commonmark",
                            "expected_results"        => "The car should also turn ",
                            "expected_results_format" => "commonmark"
                        ],
                        [
                            "description"             => "The car does not have tyre",
                            "description_format"      => "html",
                            "expected_results"        => "It should be difficult to move",
                            "expected_results_format" => "html"
                        ]
                    ],
                ]
            ],
        ];

        $response = $this->getResponse(
            $this->client->post('artifacts', null, json_encode($payload))
        );

        self::assertEquals(201, $response->getStatusCode());

        $artifact_id = $response->json()["id"];
        $this->assertArtifactHasStepDefField($artifact_id);
    }

    /**
     * @throws UsedFieldIdNotFoundException
     */
    private function getUsedFieldId(int $tracker_id, string $field_shortname): ?int
    {
        $tracker = $this->tracker_representations[$tracker_id];
        foreach ($tracker['fields'] as $tracker_field) {
            if ($tracker_field['name'] === $field_shortname) {
                return $tracker_field["field_id"];
            }
        }
        throw new UsedFieldIdNotFoundException();
    }

    private function assertArtifactHasStepDefField(int $artifact_id): void
    {
        $artifact_request = $this->client->get('artifacts/' . $artifact_id);
        $artifact         = $this->getResponse($artifact_request)->json();

        $ttmstepdef = array_filter(
            $artifact['values'],
            static function (array $value): bool {
                return $value['type'] === 'ttmstepdef';
            }
        );
        self::assertNotEmpty($ttmstepdef);
    }
}
