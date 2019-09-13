<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Test\Rest\Tracker;

use Exception;
use Guzzle\Http\Message\Response;
use Tuleap\REST\ArtifactBase;

/**
 * @group ArtifactsTest
 */
class ArtifactsTestExecutionHelper extends ArtifactBase
{
    protected function buildPOSTBodyContent($summary_field_label, $summary_field_value): string
    {
        return json_encode(
            [
                'tracker' => [
                    'id'  => $this->epic_tracker_id,
                    'uri' => 'whatever'
                ],
                'values'  => [
                    $this->getSubmitTextValue($this->epic_tracker_id, $summary_field_label, $summary_field_value),
                    $this->getSubmitListValue($this->epic_tracker_id, 'Status', 103)
                ],
            ]
        );
    }

    private function getSubmitTextValue($tracker_id, $field_label, $field_value): array
    {
        $field_def = $this->getFieldDefByFieldLabel($tracker_id, $field_label);
        return [
            'field_id' => $field_def['field_id'],
            'value'    => $field_value,
        ];
    }

    private function getSubmitListValue($tracker_id, $field_label, $field_value): array
    {
        $field_def = $this->getFieldDefByFieldLabel($tracker_id, $field_label);
        return [
            'field_id'       => $field_def['field_id'],
            'bind_value_ids' => [
                $field_value
            ],
        ];
    }

    private function getFieldDefByFieldLabel($tracker_id, $field_label): array
    {
        $tracker = $this->getTracker($tracker_id);
        foreach ($tracker['fields'] as $field) {
            if ($field['label'] == $field_label) {
                return $field;
            }
        }
    }

    protected function getFieldIdForFieldLabel($artifact_id, $field_label): int
    {
        $value = $this->getFieldByFieldLabel($artifact_id, $field_label);
        return $value['field_id'];
    }

    protected function getFieldValueForFieldLabel($artifact_id, $field_label): string
    {
        $value = $this->getFieldByFieldLabel($artifact_id, $field_label);
        return $value['value'];
    }

    protected function getFieldByFieldLabel($artifact_id, $field_label): array
    {
        $artifact = $this->getArtifact($artifact_id);
        foreach ($artifact['values'] as $value) {
            if ($value['label'] == $field_label) {
                return $value;
            }
        }
    }

    private function getArtifact($artifact_id): array
    {
        $response = $this->getResponse($this->client->get('artifacts/' . $artifact_id));
        if ($response->getStatusCode() !== 200) {
            throw new Exception($response->json()['error']['message'], $response->getStatusCode());
        }
        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));

        return $response->json();
    }

    private function getTracker($tracker_id): array
    {
        return $this->tracker_representations[$tracker_id];
    }
    protected function assertLinks(Response $response, $nature_is_child, $artifact_id, $nature_empty): void
    {
        $links = $response->json();

        $expected_link = [
            "natures" => [
                [
                    "shortname" => $nature_is_child,
                    "direction" => 'forward',
                    "label"     => "Child",
                    "uri"       => "artifacts/$artifact_id/linked_artifacts?nature=$nature_is_child&direction=forward"
                ],
                [
                    "shortname" => $nature_empty,
                    "direction" => 'forward',
                    "label"     => '',
                    "uri"       => "artifacts/$artifact_id/linked_artifacts?nature=$nature_empty&direction=forward"
                ]
            ]
        ];

        $this->assertEquals($expected_link, $links);
    }
}
