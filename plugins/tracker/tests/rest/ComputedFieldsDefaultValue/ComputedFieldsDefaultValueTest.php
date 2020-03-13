<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Tests\REST\ComputedFieldsDefaultValue;

use REST_TestDataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../TrackerBase.php';

class ComputedFieldsDefaultValueTest extends TrackerBase
{
    public function testComputedFieldHasDefaultValueKey()
    {
        $computed_field_found = false;

        $tracker_representation = $this->tracker_representations[$this->computed_value_tracker_id];

        foreach ($tracker_representation['fields'] as $field) {
            if ($field['type'] === 'computed') {
                $computed_field_found = true;
                $this->assertArrayHasKey('default_value', $field);

                $this->assertSame('manual_value', $field['default_value']['type']);
                $this->assertSame(5.2, $field['default_value']['value']);
            }
        }

        if (! $computed_field_found) {
            $this->fail('Computed field not found to check default value.');
        }
    }

    public function testProjectMembersCanCreateAnArtifactAndComputedDefaultValueIsCorrectlySet()
    {
        $string_field_id = null;
        $tracker_representation = $this->tracker_representations[$this->computed_value_tracker_id];
        foreach ($tracker_representation['fields'] as $field) {
            if ($field['type'] === 'string') {
                $string_field_id = $field['field_id'];
            }
        }

        $payload = [
            'tracker' => ['id' => $this->computed_value_tracker_id],
            'values'  => [
                [
                    'field_id' => $string_field_id,
                    'value' => 'Title 01'
                ]
            ]
        ];

        $response = $this->getResponse(
            $this->client->post('artifacts', null, json_encode($payload)),
            REST_TestDataBuilder::TEST_USER_3_NAME
        );

        $this->assertEquals(201, $response->getStatusCode());

        $created_artifact     = $response->json();
        $created_artifact_uri = $created_artifact['uri'];

        $get_response = $this->getResponse(
            $this->client->get($created_artifact_uri)
        );

        $computed_field_found = false;
        $artifact = $get_response->json();
        foreach ($artifact['values'] as $field_value) {
            if ($field_value['type'] === 'computed') {
                $computed_field_found = true;

                $this->assertFalse($field_value['is_autocomputed']);
                $this->assertSame(5.2, $field_value['manual_value']);
                $this->assertNull($field_value['value']);
            }
        }

        if (! $computed_field_found) {
            $this->fail('Computed field not found to check default value.');
        }
    }
}
