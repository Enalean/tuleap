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
 */

declare(strict_types=1);

namespace Tuleap\REST;

use Exception;
use Psl\Json;
use Psr\Http\Message\ResponseInterface;

class ArtifactsTestExecutionHelper extends ArtifactBase
{
    protected function buildPOSTBodyContent(string $summary_field_label, string $summary_field_value): string
    {
        return Json\encode(
            [
                'tracker' => [
                    'id'  => $this->epic_tracker_id,
                    'uri' => 'whatever',
                ],
                'values'  => [
                    $this->getSubmitStringValue($this->epic_tracker_id, $summary_field_label, $summary_field_value),
                    $this->getSubmitTextValue($this->epic_tracker_id, 'Required Text', 'this is a description'),
                    $this->getSubmitListValue($this->epic_tracker_id, 'Status', 103),
                ],
            ]
        );
    }

    protected function buildPOSTBodyContentWithTooBigTextContent(): string
    {
        return Json\encode(
            [
                'tracker' => [
                    'id'  => $this->epic_tracker_id,
                    'uri' => 'whatever',
                ],
                'values'  => [
                    $this->getSubmitStringValue($this->epic_tracker_id, 'Summary', 'This is a new epic'),
                    $this->getSubmitTextValue($this->epic_tracker_id, 'Required Text', str_repeat('a', 70000)),
                    $this->getSubmitListValue($this->epic_tracker_id, 'Status', 103),
                ],
            ]
        );
    }

    private function getSubmitStringValue(int $tracker_id, string $field_label, string $field_value): array
    {
        $field_def = $this->getFieldDefByFieldLabel($tracker_id, $field_label);
        return [
            'field_id' => $field_def['field_id'],
            'value'    => $field_value,
        ];
    }

    private function getSubmitTextValue(int $tracker_id, string $field_label, string $field_value): array
    {
        $field_def = $this->getFieldDefByFieldLabel($tracker_id, $field_label);
        return [
            'field_id' => $field_def['field_id'],
            'value'    => ['format' => 'html', 'content' => $field_value],
        ];
    }

    private function getSubmitListValue(int $tracker_id, string $field_label, int $field_value): array
    {
        $field_def = $this->getFieldDefByFieldLabel($tracker_id, $field_label);
        return [
            'field_id'       => $field_def['field_id'],
            'bind_value_ids' => [
                $field_value,
            ],
        ];
    }

    private function getFieldDefByFieldLabel(int $tracker_id, string $field_label): array
    {
        $tracker = $this->getTracker($tracker_id);
        foreach ($tracker['fields'] as $field) {
            if ($field['label'] === $field_label) {
                return $field;
            }
        }
        throw new \RuntimeException(sprintf('Could not find field "%s"', $field_label));
    }

    protected function getFieldIdForFieldLabel(int $artifact_id, string $field_label): int
    {
        $value = $this->getFieldByFieldLabel($artifact_id, $field_label);
        return $value['field_id'];
    }

    protected function getFieldValueForFieldLabel(int $artifact_id, string $field_label): string
    {
        $value = $this->getFieldByFieldLabel($artifact_id, $field_label);
        return $value['value'];
    }

    protected function getFieldByFieldLabel(int $artifact_id, string $field_label): array
    {
        $artifact = $this->getArtifact($artifact_id);
        foreach ($artifact['values'] as $value) {
            if ($value['label'] === $field_label) {
                return $value;
            }
        }
        throw new \RuntimeException(sprintf('Could not find field "%s"', $field_label));
    }

    private function getArtifact(int $artifact_id): array
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id));
        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                Json\decode($response->getBody()->getContents())['error']['message'],
                $response->getStatusCode()
            );
        }
        self::assertNotEmpty($response->getHeader('Last-Modified'));
        self::assertNotEmpty($response->getHeader('Etag'));

        return Json\decode($response->getBody()->getContents());
    }

    private function getTracker(int $tracker_id): array
    {
        return $this->tracker_representations[$tracker_id];
    }

    protected function assertLinks(ResponseInterface $response, string $nature_is_child, int $artifact_id, string $nature_empty): void
    {
        $links = Json\decode($response->getBody()->getContents());

        $expected_link = [
            'natures' => [
                [
                    'shortname' => $nature_is_child,
                    'direction' => 'forward',
                    'label'     => 'Child',
                    'uri'       => "artifacts/$artifact_id/linked_artifacts?nature=$nature_is_child&direction=forward",
                ],
                [
                    'shortname' => $nature_empty,
                    'direction' => 'forward',
                    'label'     => '',
                    'uri'       => "artifacts/$artifact_id/linked_artifacts?nature=$nature_empty&direction=forward",
                ],
            ],
        ];

        self::assertEquals($expected_link, $links);
    }
}
