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

namespace Tuleap\TestManagement\REST;

use Tuleap\TestManagement\REST\Tests\TestManagementDataBuilder;
use Tuleap\TestManagement\REST\Tests\TestManagementRESTTestCase;
use Tuleap\TestManagement\REST\Tests\UsedFieldIdNotFoundException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactsTest extends TestManagementRESTTestCase
{
    public function testItPostAnArtifactWithStepDefinitionFieldId(): int
    {
        $test_def_tracker_id = $this->tracker_ids[$this->project_id][TestManagementDataBuilder::TEST_DEF_TRACKER_SHORTNAME];
        $summary_field_id    = $this->getUsedFieldId($test_def_tracker_id, 'summary');
        $details_field_id    = $this->getUsedFieldId($test_def_tracker_id, 'details');
        $steps_field_id      = $this->getUsedFieldId($test_def_tracker_id, 'steps');

        $payload = [
            'tracker' => ['id' => $test_def_tracker_id],
            'values'  => [
                [
                    'field_id' => $summary_field_id,
                    'value'    => 'Test if the car is usable or not',
                ],
                [
                    'field_id' => $details_field_id,
                    'value'    => 'Cars go brrrrr',
                ],
                [
                    'field_id' => $steps_field_id,
                    'value'    => [
                        [
                            'description'             => 'You can turn the steering wheel',
                            'description_format'      => 'commonmark',
                            'expected_results'        => 'The car should also turn ',
                            'expected_results_format' => 'commonmark',
                        ],
                        [
                            'description'             => 'The car does not have tyre',
                            'description_format'      => 'html',
                            'expected_results'        => 'It should be difficult to move',
                            'expected_results_format' => 'html',
                        ],
                        [
                            'description'             => 'The car does not have tyre',
                            'description_format'      => 'text',
                            'expected_results'        => 'It should be difficult to move',
                            'expected_results_format' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream(json_encode($payload)))
        );

        self::assertEquals(201, $response->getStatusCode());

        $artifact_id = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];
        $this->assertArtifactHasStepDefField($artifact_id);

        return $artifact_id;
    }

    public function testItPostAnArtifactWithStepDefinitionFieldName(): void
    {
        $test_def_tracker_id = $this->tracker_ids[$this->project_id][TestManagementDataBuilder::TEST_DEF_TRACKER_SHORTNAME];

        $payload = [
            'tracker'         => ['id' => $test_def_tracker_id],
            'values_by_field' => [
                'summary' => ['value' => 'Test if the car is usable or not'],
                'details' => ['value' => 'Cars go brrrrr'],
                'steps'   => [
                    'value' => [
                        [
                            'description'             => 'You can turn the steering wheel',
                            'description_format'      => 'commonmark',
                            'expected_results'        => 'The car should also turn ',
                            'expected_results_format' => 'commonmark',
                        ],
                        [
                            'description'             => 'The car does not have tyre',
                            'description_format'      => 'html',
                            'expected_results'        => 'It should be difficult to move',
                            'expected_results_format' => 'html',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream(json_encode($payload)))
        );

        self::assertEquals(201, $response->getStatusCode());

        $artifact_id = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];
        $this->assertArtifactHasStepDefField($artifact_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testItPostAnArtifactWithStepDefinitionFieldId')]
    public function testUpdatesArtifactStepDef(int $artifact_id): void
    {
        $field_value = $this->getArtifactStepDefFieldValue($artifact_id);
        self::assertNotNull($field_value);

        $field_value['value'] = [
            [
                'description' => 'D10',
                'description_format' => 'html',
                'expected_results' => 'R10',
                'expected_results_format' => 'html',
                'rank' => 10,
            ],
            [
                'description' => 'D1',
                'description_format' => 'html',
                'expected_results' => 'R1',
                'expected_results_format' => 'html',
                'rank' => 1,
            ],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artifacts/' . urlencode((string) $artifact_id))->withBody(
                $this->stream_factory->createStream(
                    json_encode(['values' => [$field_value], 'comment' => ['body' => 'Update step def', 'format' => 'text']], JSON_THROW_ON_ERROR)
                )
            )
        );
        self::assertEquals(200, $response->getStatusCode());

        $updated_step_field_value                       = $this->getArtifactStepDefFieldValue($artifact_id);
        $updated_step_field_definition_value_without_id = [];
        foreach ($updated_step_field_value['value'] as $step_def_value) {
            unset($step_def_value['id']);
            $updated_step_field_definition_value_without_id[] = $step_def_value;
        }
        self::assertEquals(
            [
                [
                    'description' => 'D1',
                    'description_format' => 'html',
                    'expected_results' => 'R1',
                    'expected_results_format' => 'html',
                    'rank' => 1,
                ],
                [
                    'description' => 'D10',
                    'description_format' => 'html',
                    'expected_results' => 'R10',
                    'expected_results_format' => 'html',
                    'rank' => 2,
                ],
            ],
            $updated_step_field_definition_value_without_id
        );

        $field_value['value'] = [];
        $response             = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artifacts/' . urlencode((string) $artifact_id))->withBody(
                $this->stream_factory->createStream(
                    json_encode(['values' => [$field_value], 'comment' => ['body' => 'Empty step def', 'format' => 'text']], JSON_THROW_ON_ERROR)
                )
            )
        );
        self::assertEquals(200, $response->getStatusCode());

        $emptied_step_field_value = $this->getArtifactStepDefFieldValue($artifact_id);
        self::assertEquals([], $emptied_step_field_value['value']);
    }

    /**
     * @throws UsedFieldIdNotFoundException
     */
    private function getUsedFieldId(int $tracker_id, string $field_shortname): ?int
    {
        $tracker = $this->tracker_representations[$tracker_id];
        foreach ($tracker['fields'] as $tracker_field) {
            if ($tracker_field['name'] === $field_shortname) {
                return $tracker_field['field_id'];
            }
        }
        throw new UsedFieldIdNotFoundException();
    }

    private function assertArtifactHasStepDefField(int $artifact_id): void
    {
        self::assertNotNull($this->getArtifactStepDefFieldValue($artifact_id));
    }

    private function getArtifactStepDefFieldValue(int $artifact_id): ?array
    {
        $artifact_request = $this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id);
        $response         = $this->getResponse($artifact_request);
        self::assertEquals(200, $response->getStatusCode());
        $artifact = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($artifact['values'] as $value) {
            if ($value['type'] === 'ttmstepdef') {
                return $value;
            }
        }

        return null;
    }
}
