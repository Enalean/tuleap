<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tests\REST\OpenLists;

use Tuleap\Tracker\Tests\REST\TrackerBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OpenListsTest extends TrackerBase
{
    public function testGetArtifactOpenListsValues(): int
    {
        $artifact_id = $this->open_list_artifact_id;

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id")
        );

        self::assertSame(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $valid_bind_value_id = 0;

        $nb_assertion = 0;
        foreach ($json['values'] as $field_values) {
            if ($field_values['label'] === 'StaticOpenList') {
                self::assertEqualsCanonicalizing(
                    ['value01', 'value03'],
                    $field_values['bind_value_ids'],
                );
                $valid_bind_value_id = $field_values['bind_value_objects'][0]['id'];
                $nb_assertion++;
            } elseif ($field_values['label'] === 'UserOpenList') {
                self::assertEqualsCanonicalizing(
                    ['Test User 1 (rest_api_tester_1)'],
                    $field_values['bind_value_ids'],
                );
                $nb_assertion++;
            } elseif ($field_values['label'] === 'UGroupOpenList') {
                self::assertEqualsCanonicalizing(
                    ['Project members'],
                    $field_values['bind_value_ids'],
                );
                $nb_assertion++;
            }
        }

        if ($nb_assertion < 3) {
            self::fail('Not all open list fields have been checked.');
        }

        return $valid_bind_value_id;
    }

    public function testFiltersOnAnUnknownOpenListValuesReturnsNothing(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                "trackers/{$this->open_list_tracker_id}/artifacts?query=" . urlencode('{"StaticOpenList":{"operator":"contains","value":[99999999999999]}}')
            )
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('0', $response->getHeaderLine('x-pagination-size'));
        self::assertSame([], json_decode($response->getBody()->getContents(), true, 2, JSON_THROW_ON_ERROR));
    }

    /**
     * @depends testGetArtifactOpenListsValues
     */
    public function testFiltersOnABindValue(int $bind_value_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                "trackers/{$this->open_list_tracker_id}/artifacts?query=" . urlencode('{"StaticOpenList":{"operator":"contains","value":[' . $bind_value_id . ']}}')
            )
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('1', $response->getHeaderLine('x-pagination-size'));
    }

    public function testFiltersOnAnOpenValue(): void
    {
        $response_tracker_format = $this->getResponse(
            $this->request_factory->createRequest('GET', "trackers/{$this->open_list_tracker_id}")
        );
        self::assertSame(200, $response_tracker_format->getStatusCode());

        $tracker_format = json_decode($response_tracker_format->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $static_value_field_id = 0;
        foreach ($tracker_format['fields'] as $field) {
            if ($field['label'] === 'StaticOpenList') {
                $static_value_field_id = $field['field_id'];
                break;
            }
        }

        $new_open_value = 'test_open_' . time();

        $response_creation = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode([
                            'tracker' => ['id' => $this->open_list_tracker_id],
                            'values' => [
                                [
                                    'field_id' => $static_value_field_id,
                                    'value' => ['bind_value_objects' => [['label' => $new_open_value]]],
                                ],
                            ],
                        ])
                    )
                )
        );
        $json_creation     = json_decode($response_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(201, $response_creation->getStatusCode());

        $artifact_id = $json_creation['id'];

        $new_artifact_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $artifact_id))
        );

        self::assertSame(200, $new_artifact_response->getStatusCode());
        $new_artifact_content = json_decode($new_artifact_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $open_list_has_the_expected_value = false;
        foreach ($new_artifact_content['values'] as $field_value) {
            if ($field_value['label'] === 'StaticOpenList') {
                $open_list_has_the_expected_value = count($field_value['bind_value_objects']) === 1 &&
                    $field_value['bind_value_objects'][0]['label'] === $new_open_value;
            }
        }

        self::assertTrue($open_list_has_the_expected_value);
    }
}
