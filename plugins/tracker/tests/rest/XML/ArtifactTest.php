<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All rights reserved
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

namespace Tracker;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('TrackerTests')]
class ArtifactTest extends TrackerBase
{
    protected $project_id;
    protected $tracker_id;
    protected $slogan_field_id;
    protected $desc_field_id;
    protected $status_field_id;
    protected $status_value_id;
    protected $release_name_field_id;
    protected $release_status_field_id;
    protected $release_status_current_value_id;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->getReleaseArtifactIds();

        $tracker          = $this->getTracker();
        $this->tracker_id = $tracker['id'];

        foreach ($tracker['fields'] as $field) {
            if ($field['name'] === 'slogan') {
                $this->slogan_field_id = $field['field_id'];
            } elseif ($field['name'] === 'epic_desc') {
                $this->desc_field_id = $field['field_id'];
            } elseif ($field['name'] === 'status') {
                $this->status_field_id = $field['field_id'];
                $this->status_value_id = $field['values'][0]['id'];
            }
        }

        $this->getReleaseTrackerInformation();
    }

    private function getXMLResponse(RequestInterface $request): ResponseInterface
    {
        return $this->getResponse(
            $request
                ->withHeader('Content-Type', 'application/xml; charset=UTF8')
                ->withHeader('Accept', 'application/xml')
        );
    }

    private function getReleaseTrackerInformation()
    {
        if (
            $this->release_name_field_id &&
            $this->release_status_field_id &&
            $this->release_status_current_value_id
        ) {
            return;
        }

        $release_tracker = $this->tracker_representations[$this->releases_tracker_id];
        foreach ($release_tracker['fields'] as $field) {
            if ($field['name'] === 'name') {
                $this->release_name_field_id = $field['field_id'];
            } elseif ($field['name'] === 'status') {
                $this->release_status_field_id = $field['field_id'];
                foreach ($field['values'] as $value) {
                    if ($value['label'] === 'Current') {
                        $this->release_status_current_value_id = $value['id'];
                    }
                }
            }
        }
    }

    private function getTracker()
    {
        return $this->tracker_representations[$this->rest_xml_api_tracker_id];
    }

    public function testGetArtifact()
    {
        $response = $this->getXMLResponse($this->request_factory->createRequest('GET', 'artifacts/' . $this->release_artifact_ids[1]));
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact_xml = new SimpleXMLElement($response->getBody()->getContents());

        $this->assertEquals((int) $artifact_xml->id, $this->release_artifact_ids[1]);
        $this->assertEquals((int) $artifact_xml->project->id, $this->project_private_member_id);
    }

    public function testPOSTArtifact()
    {
        $xml = "<request><tracker><id>$this->releases_tracker_id</id></tracker><values><item><field_id>" .
            $this->release_name_field_id . '</field_id><value>Test Release</value></item><item><field_id>' .
            $this->release_status_field_id . '</field_id><bind_value_ids><item>' .
            $this->release_status_current_value_id . '</item></bind_value_ids></item></values></request>';

        $response = $this->getXMLResponse($this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream($xml)));

        $this->assertEquals($response->getStatusCode(), 201);
        $artifact_xml = new SimpleXMLElement($response->getBody()->getContents());

        $artifact_id = (int) $artifact_xml->id;
        $this->assertGreaterThan(0, $artifact_id);

        return $artifact_id;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPOSTArtifact')]
    public function testPUTArtifact($artifact_id)
    {
        $new_value = 'Test Release Updated';
        $xml       = "<request><tracker><id>$this->releases_tracker_id</id></tracker><values><item><field_id>" .
            $this->release_name_field_id . '</field_id><value>' . $new_value . '</value></item></values></request>';

        $response = $this->getXMLResponse($this->request_factory->createRequest('PUT', 'artifacts/' . $artifact_id)->withBody($this->stream_factory->createStream($xml)));

        $this->assertEquals(200, $response->getStatusCode());
        $artifact_xml = new SimpleXMLElement($this->getXMLResponse($this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id))->getBody()->getContents());

        $field_value = null;
        foreach ($artifact_xml->values->item as $field_content) {
            if ($this->release_name_field_id === (int) $field_content->field_id) {
                $field_value = $field_content->value;
                break;
            }
        }

        $this->assertEquals($new_value, (string) $field_value);
    }

    public function testPOSTArtifactInXMLTracker()
    {
        $xml = '<request><tracker><id>' . $this->tracker_id . '</id></tracker><values><item><field_id>' . $this->slogan_field_id . '</field_id><value>slogan</value></item><item><field_id>' . $this->desc_field_id . '</field_id><value>desc</value></item><item><field_id>' . $this->status_field_id . '</field_id><bind_value_ids><item>' . $this->status_value_id . '</item></bind_value_ids></item></values></request>';

        $response = $this->getXMLResponse($this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream($xml)));

        $this->assertEquals($response->getStatusCode(), 201);
        $artifact_xml = new SimpleXMLElement($response->getBody()->getContents());

        $artifact_id = (int) $artifact_xml->id;
        $this->assertGreaterThan(0, $artifact_id);

        return $artifact_id;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPOSTArtifactInXMLTracker')]
    public function testGetArtifactInXMLTracker($artifact_id)
    {
        $response = $this->getXMLResponse($this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id));
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact_xml = new SimpleXMLElement($response->getBody()->getContents());

        $this->assertEquals((int) $artifact_xml->id, $artifact_id);
        $this->assertEquals((int) $artifact_xml->project->id, $this->rest_xml_api_project_id);

        $this->assertGreaterThan(0, count($artifact_xml->values->children()));
        $this->assertCount(0, $artifact_xml->values_by_field->children());

        $this->verifySloganAndStatusFieldPresenceAndValue($artifact_xml->values->item);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPOSTArtifactInXMLTracker')]
    public function testGetArtifactInXMLTrackerWithValuesByField($artifact_id)
    {
        $response = $this->getXMLResponse($this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id . '?values_format=by_field'));
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact_xml = new SimpleXMLElement($response->getBody()->getContents());

        $this->assertEquals((int) $artifact_xml->id, $artifact_id);
        $this->assertEquals((int) $artifact_xml->project->id, $this->rest_xml_api_project_id);

        $this->assertEquals(0, count($artifact_xml->values->children()));
        $this->assertGreaterThan(0, count($artifact_xml->values_by_field->children()));

        $this->assertEquals((string) $artifact_xml->values_by_field->slogan->value, 'slogan');
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPOSTArtifactInXMLTracker')]
    public function testGetArtifactInXMLTrackerInBothFormat($artifact_id)
    {
        $response = $this->getXMLResponse($this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id . '?values_format=all'));
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact_xml = new SimpleXMLElement($response->getBody()->getContents());

        $this->assertEquals((int) $artifact_xml->id, $artifact_id);
        $this->assertEquals((int) $artifact_xml->project->id, $this->rest_xml_api_project_id);

        $this->assertGreaterThan(0, count($artifact_xml->values->children()));
        $this->assertGreaterThan(0, count($artifact_xml->values_by_field->children()));

        $this->verifySloganAndStatusFieldPresenceAndValue($artifact_xml->values->item);

        $this->assertEquals((string) $artifact_xml->values_by_field->slogan->value, 'slogan');
    }

    private function verifySloganAndStatusFieldPresenceAndValue(SimpleXMLElement $items): void
    {
        $this->assertTrue(
            (static function (SimpleXMLElement $items): bool {
                foreach ($items as $item) {
                    if ((string) $item->label === 'Slogan' && (string) $item->value === 'slogan') {
                        return true;
                    }
                }
                return false;
            })($items),
            'Slogan field not found or with an incorrect value'
        );
        $this->assertTrue(
            (function (SimpleXMLElement $items): bool {
                foreach ($items as $item) {
                    if (
                        (string) $item->label === 'Status' &&
                        (string) $item->values->item->label === 'SM New' &&
                        (int) $item->bind_value_ids === $this->status_value_id
                    ) {
                        return true;
                    }
                }
                return false;
            })($items),
            'Status field not found or with an incorrect value'
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetArtifactInXMLTrackerInBothFormat')]
    public function testPOSTArtifactInXMLTrackerWithValuesByField()
    {
        $xml = '<request><tracker><id>' . $this->tracker_id . '</id></tracker><values_by_field><slogan><value>Sloganv2</value></slogan><epic_desc><value><content>Descv2</content><format>html</format></value></epic_desc></values_by_field></request>';

        $response = $this->getXMLResponse($this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream($xml)));

        $this->assertEquals($response->getStatusCode(), 201);
        $artifact_xml = new SimpleXMLElement($response->getBody()->getContents());

        $artifact_id = (int) $artifact_xml->id;
        $this->assertGreaterThan(0, $artifact_id);

        return $artifact_id;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPOSTArtifactInXMLTrackerWithValuesByField')]
    public function testGetArtifactCreatedWithValueByFieldInXMLTracker($artifact_id)
    {
        $response = $this->getXMLResponse($this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id . '?values_format=by_field'));
        $this->assertEquals(200, $response->getStatusCode());

        $artifact_xml = new SimpleXMLElement($response->getBody()->getContents());

        $this->assertEquals((int) $artifact_xml->id, $artifact_id);
        $this->assertEquals((int) $artifact_xml->project->id, $this->rest_xml_api_project_id);

        $this->assertEquals(0, count($artifact_xml->values->children()));
        $this->assertGreaterThan(0, count($artifact_xml->values_by_field->children()));

        $this->assertEquals((string) $artifact_xml->values_by_field->slogan->value, 'Sloganv2');
        $this->assertEquals((string) $artifact_xml->values_by_field->epic_desc->format, 'html');
        $this->assertEquals((string) $artifact_xml->values_by_field->epic_desc->value, 'Descv2');
    }
}
