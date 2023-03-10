<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\Project\Components;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;

final class ComponentsTrackerBuilderTest extends TestCase
{
    public function testCompontentTrackerStructureIsTheOneExpected(): void
    {
        $xml_tracker = (new ComponentsTrackerBuilder())->get(new FieldAndValueIDGenerator());
        $xml         = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');
        $xml_tracker->export($xml->trackers);

        self::assertEquals(\Tracker_FormElementFactory::CONTAINER_FIELDSET_TYPE, (string) $xml->trackers->tracker->formElements->formElement[0]['type']);
        $details_fieldset = $xml->trackers->tracker->formElements->formElement[0];

        self::assertNotNull($details_fieldset->formElements->formElement[0]);
        $name_field = $details_fieldset->formElements->formElement[0];
        self::assertEquals('Name', (string) $name_field->label);
        self::assertEquals($name_field['ID'], (string) $xml->trackers->tracker->reports->report[0]->renderers->renderer[0]->columns->field[0]['REF']);
        self::assertEquals($name_field['ID'], (string) $xml->trackers->tracker->reports->report[0]->criterias->criteria[0]->field['REF']);
        $permissions = $xml->xpath(sprintf('/project/trackers/tracker/permissions/permission[@REF="%s"]', (string) $name_field['ID']));
        self::assertCount(3, $permissions);
        self::assertEquals('PLUGIN_TRACKER_FIELD_READ', $permissions[0]['type']);
        self::assertEquals('PLUGIN_TRACKER_FIELD_SUBMIT', $permissions[1]['type']);
        self::assertEquals('PLUGIN_TRACKER_FIELD_UPDATE', $permissions[2]['type']);

        self::assertNotNull($details_fieldset->formElements->formElement[1]);
        $description_field = $details_fieldset->formElements->formElement[1];
        self::assertEquals('Description', (string) $description_field->label);
        self::assertEquals($description_field['ID'], (string) $xml->trackers->tracker->reports->report[0]->renderers->renderer[0]->columns->field[1]['REF']);
        self::assertEquals($description_field['ID'], (string) $xml->trackers->tracker->reports->report[0]->criterias->criteria[1]->field['REF']);
        $permissions = $xml->xpath(sprintf('/project/trackers/tracker/permissions/permission[@REF="%s"]', (string) $description_field['ID']));
        self::assertCount(3, $permissions);
        self::assertEquals('PLUGIN_TRACKER_FIELD_READ', $permissions[0]['type']);
        self::assertEquals('PLUGIN_TRACKER_FIELD_SUBMIT', $permissions[1]['type']);
        self::assertEquals('PLUGIN_TRACKER_FIELD_UPDATE', $permissions[2]['type']);

        self::assertNotNull($details_fieldset->formElements->formElement[2]);
        $component_lead_field = $details_fieldset->formElements->formElement[2];
        self::assertEquals('Component Lead', (string) $component_lead_field->label);
        self::assertEquals($component_lead_field['ID'], (string) $xml->trackers->tracker->reports->report[0]->renderers->renderer[0]->columns->field[2]['REF']);
        self::assertEquals($component_lead_field['ID'], (string) $xml->trackers->tracker->reports->report[0]->criterias->criteria[2]->field['REF']);
        $permissions = $xml->xpath(sprintf('/project/trackers/tracker/permissions/permission[@REF="%s"]', (string) $component_lead_field['ID']));
        self::assertCount(3, $permissions);
        self::assertEquals('PLUGIN_TRACKER_FIELD_READ', $permissions[0]['type']);
        self::assertEquals('PLUGIN_TRACKER_FIELD_SUBMIT', $permissions[1]['type']);
        self::assertEquals('PLUGIN_TRACKER_FIELD_UPDATE', $permissions[2]['type']);

        self::assertEquals(\Tracker_FormElementFactory::CONTAINER_FIELDSET_TYPE, (string) $xml->trackers->tracker->formElements->formElement[1]['type']);
        $links_fieldset = $xml->trackers->tracker->formElements->formElement[1];

        self::assertNotNull($links_fieldset->formElements->formElement[0]);
        $link_field = $links_fieldset->formElements->formElement[0];
        self::assertEquals('Links', (string) $link_field->label);
        $permissions = $xml->xpath(sprintf('/project/trackers/tracker/permissions/permission[@REF="%s"]', (string) $link_field['ID']));
        self::assertCount(3, $permissions);
        self::assertEquals('PLUGIN_TRACKER_FIELD_READ', $permissions[0]['type']);
        self::assertEquals('PLUGIN_TRACKER_FIELD_SUBMIT', $permissions[1]['type']);
        self::assertEquals('PLUGIN_TRACKER_FIELD_UPDATE', $permissions[2]['type']);
    }
}
