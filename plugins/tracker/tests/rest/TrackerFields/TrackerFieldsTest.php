<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
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

namespace Tuleap\Tracker\Tests\REST\TrackerFields;

use Tuleap\Tracker\Tests\REST\TrackerBase;

class TrackerFieldsTest extends TrackerBase
{
    const FIELD_STATIC_SELECTBOX_SHOTNAME       = 'staticsb';
    const FIELD_STATIC_RADIOBUTTON_SHOTNAME     = 'staticrb';
    const FIELD_STATIC_MULTI_SELECTBOX_SHOTNAME = 'staticmsb';
    const FIELD_USER_SELECTBOX_SHOTNAME         = 'userssb';

    public function testOPTIONSId()
    {
        $field_id = $this->getStaticSelectboxFieldId();

        $response = $this->getResponse($this->client->options("tracker_fields/$field_id"));
        $this->assertEquals(array('OPTIONS', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPATCHAddsNewValuesInSelectboxBindToStaticValues()
    {
        $field_id = $this->getStaticSelectboxFieldId();
        $body     = json_encode([
            "new_values" => ['new_value_01', 'new_value_02']
        ]);

        $response = $this->getResponse($this->client->patch("tracker_fields/$field_id", null, $body));

        $this->assertEquals($response->getStatusCode(), 200);

        $tracker_field_json = $response->json();

        $this->assertEquals(count($tracker_field_json['values']), 7);
        $this->assertEquals($tracker_field_json['values'][5]['label'], 'new_value_01');
        $this->assertEquals($tracker_field_json['values'][6]['label'], 'new_value_02');
    }

    public function testPATCHAddsNewValuesInRadiobuttonBindToStaticValues()
    {
        $field_id = $this->getStaticRadiobuttonFieldId();
        $body     = json_encode([
            "new_values" => ['new_value_01', 'new_value_02']
        ]);

        $response = $this->getResponse($this->client->patch("tracker_fields/$field_id", null, $body));

        $this->assertEquals($response->getStatusCode(), 200);

        $tracker_field_json = $response->json();

        $this->assertEquals(count($tracker_field_json['values']), 7);
        $this->assertEquals($tracker_field_json['values'][5]['label'], 'new_value_01');
        $this->assertEquals($tracker_field_json['values'][6]['label'], 'new_value_02');
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPATCHThrowsAnExceptionIfFieldIsNotASimpleList()
    {
        $field_id = $this->getStaticMultiSelectboxFieldId();
        $body     = json_encode([
            "new_values" => ['new_value_01', 'new_value_02']
        ]);

        $response = $this->getResponse($this->client->patch("tracker_fields/$field_id", null, $body));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPATCHThrowsAnExceptionIfFieldIsNotBoundToStaticValues()
    {
        $field_id = $this->getUserSelectboxFieldId();
        $body     = json_encode([
            "new_values" => ['new_value_01', 'new_value_02']
        ]);

        $response = $this->getResponse($this->client->patch("tracker_fields/$field_id", null, $body));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    private function getStaticMultiSelectboxFieldId()
    {
        $response     = $this->getResponse($this->client->get('trackers/' . $this->tracker_fields_tracker_id));
        $tracker_json = $response->json();

        return $this->getFieldId($tracker_json["fields"], self::FIELD_STATIC_MULTI_SELECTBOX_SHOTNAME);
    }

    private function getStaticSelectboxFieldId()
    {
        $response     = $this->getResponse($this->client->get('trackers/' . $this->tracker_fields_tracker_id));
        $tracker_json = $response->json();

        return $this->getFieldId($tracker_json["fields"], self::FIELD_STATIC_SELECTBOX_SHOTNAME);
    }

    private function getUserSelectboxFieldId()
    {
        $response     = $this->getResponse($this->client->get('trackers/' . $this->tracker_fields_tracker_id));
        $tracker_json = $response->json();

        return $this->getFieldId($tracker_json["fields"], self::FIELD_USER_SELECTBOX_SHOTNAME);
    }

    private function getStaticRadiobuttonFieldId()
    {
        $response     = $this->getResponse($this->client->get('trackers/' . $this->tracker_fields_tracker_id));
        $tracker_json = $response->json();

        return $this->getFieldId($tracker_json["fields"], self::FIELD_STATIC_RADIOBUTTON_SHOTNAME);
    }

    private function getFieldId(array $tracker_fields_json, $field_shortname)
    {
        foreach ($tracker_fields_json as $tracker_field_json) {
            if ($tracker_field_json["name"] === $field_shortname) {
                return $tracker_field_json["field_id"];
            }
        }

        return null;
    }
}
